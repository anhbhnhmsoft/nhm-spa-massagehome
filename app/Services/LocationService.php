<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Jobs\ProcessCachingPlaceJob;
use App\Repositories\GeoCachingPlaceRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LocationService  extends BaseService
{
    protected ?array $config = null;

    const URL = 'https://rsapi.goong.io/place/';
    const ENDPOINT_DETAIL      = 'detail';
    const ENDPOINT_AUTOCOMPLETE = 'autocomplete';
    public function __construct(
        protected  ConfigService $configService,
        protected GeoCachingPlaceRepository $geoCachingPlaceRepository,
    ) {
        parent::__construct();
        $this->initConfigGoong();
    }

    /**
     * --------- protected methods ---------
     */
    /**
     * Khởi tạo api key goong.
     * @return void
     */
    protected function initConfigGoong(): void
    {
        $apiKey = $this->configService->getConfig(ConfigName::GOONG_API_KEY);
        if ($apiKey->isSuccess()) {
            $this->config =  [
                'api_key' => $apiKey->getData()['config_value'],
            ];
        } else {
            $this->config = null;
            LogHelper::error(message: "Lỗi không tìm thấy cấu hình goong");
        }
    }

    /**
     * Lấy cấu hình goong.
     * @return array Cấu hình goong.
     * @throws ServiceException
     */
    protected function getConfigGoong(): array
    {
        if (empty($this->config)) {
            throw new ServiceException(__('common_error.empty_config'));
        }
        return $this->config;
    }

    /**
     * Tạo đường dẫn api hoàn chỉnh
     * @param string $link
     * @return string
     */
    protected function createLink(string $link): string
    {
        return self::URL . $link;
    }

    /**
     *
     * --------- public methods ---------
     *
     */
    public function getDetail(string $placeId): ServiceReturn
    {
        try {
            if (empty($placeId)) {
                return ServiceReturn::success([]);
            }

            /**
             * Check cache first
             */

            $cached = Caching::getCache(CacheKey::CACHE_USER_LOCATION, $placeId);
            if ($cached) {
                return ServiceReturn::success([
                    'place_id'          => $cached->place_id,
                    'formatted_address' => $cached->formatted_address,
                    'latitude'          => $cached->latitude,
                    'longitude'         => $cached->longitude,
                ]);
            }
            /** 1. Cache check */
            $cached = $this->geoCachingPlaceRepository->findByPlaceId($placeId);

            if ($cached) {

                if (!empty($cached) && $cached && isset($cached->latitude) && isset($cached->longitude)) {
                    Caching::setCache(CacheKey::CACHE_USER_LOCATION, $cached, $placeId);

                    return ServiceReturn::success([
                        'place_id'          => $cached->place_id,
                        'formatted_address' => $cached->formatted_address,
                        'latitude'          => $cached->latitude,
                        'longitude'         => $cached->longitude,
                    ]);
                }
            }


            /** 2. Call API */
            $config = $this->getConfigGoong();

            $params = [
                'place_id' => $placeId,
                'api_key'  => $config['api_key'],
            ];

            $response = Http::timeout(10)->get(
                $this->createLink(self::ENDPOINT_DETAIL . '?' . http_build_query($params))
            );
            $data = $response->json();
            if ($response->failed() || ($data['status'] ?? 'OK') !== 'OK') {
                $status = $data['status'] ?? $response->status();
                LogHelper::error("Goong API Error: " . $response->body() . " | Place ID: " . $placeId);
                $errorMessage = __('error.goong_error', ['status' => $status . " - ID: " . $placeId]);
                throw new ServiceException($errorMessage);
            }

            $data = $response->json();

            if (empty($data['result'])) {
                return ServiceReturn::success([]);
            }

            $result = $data['result'];
            $locationData = $result['geometry']['location'] ?? [];

            $name = $result['name'] ?? null;
            $address = $result['formatted_address'] ?? null;
            $formattedAddress = implode(', ', array_filter([$name, $address]));

            $dataToCache = [
                'place_id'          => $result['place_id'] ?? $placeId,
                'formatted_address' => $formattedAddress,
                'latitude'          => $locationData['lat'] ?? null,
                'longitude'         => $locationData['lng'] ?? null,
                'raw_data'          => json_encode($result),
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            /** 3. Cache job */
            ProcessCachingPlaceJob::dispatch([$dataToCache]);

            /** 4. Trả output chuẩn hoá */
            return ServiceReturn::success([
                'place_id'          => $result['place_id'] ?? $placeId,
                'formatted_address' => $formattedAddress,
                'latitude'          => $locationData['lat'] ?? null,
                'longitude'         => $locationData['lng'] ?? null,
            ]);
        } catch (ConnectionException $e) {
            LogHelper::error("Lỗi LocationService@autoComplete", ex: $e);
            return ServiceReturn::error(__('common_error.goong_error'));
        } catch (ServiceException $e) {
            LogHelper::error("Lỗi LocationService@autoComplete", ex: $e);
            return ServiceReturn::error($e->getMessage());
        } catch (\Exception $e) {
            LogHelper::error("Lỗi LocationService@getDetail", ex: $e);
            return ServiceReturn::error(__('common_error.goong_error'));
        }
    }


    public function autoComplete(string $keyword, float $longitude = 0, float $latitude = 0, int $limit = 9, int $radius = 10)
    {
        try {
            if (empty($keyword)) {
                return ServiceReturn::success([]);
            }

            $config = $this->getConfigGoong();

            /** 1. CHECK CACHE */
            $cached = $this->geoCachingPlaceRepository->filterQuery(
                $this->geoCachingPlaceRepository->query(),
                ['keyword' => $keyword]
            )
                ->limit($limit)
                ->get();

            if ($cached->count() >= $limit) {
                return ServiceReturn::success(
                    $cached->map(fn($p) => [
                        'formatted_address' => $p->formatted_address,
                        'place_id'          => $p->place_id,
                    ])->toArray()
                );
            }

            /** 2. Build API params */
            $params = [
                'input'   => $keyword,
                'limit'   => $limit,
                'radius'  => $radius,
                'api_key' => $config['api_key'],
            ];

            // chỉ thêm location nếu hợp lệ
            if ($longitude != 0 && $latitude != 0) {
                $params['location'] = "{$latitude},{$longitude}";
            }

            $link = $this->createLink(self::ENDPOINT_AUTOCOMPLETE . '?' . http_build_query($params));

            /** 3. Call API */
            $response = Http::timeout(10)->get($link);

            if (!$response->successful()) {
                throw new ServiceException(__('common_error.goong_error'));
            }

            $data = $response->json();

            /** 4. Prepare caching (CHỈ cache nếu API trả ra kết quả) */
            if (!empty($data['predictions'])) {
                $placesToCache = [];
                $dataReturn = [];

                foreach ($data['predictions'] as $item) {
                    $placesToCache[] = [
                        'place_id'          => $item['place_id'],
                        'formatted_address' => $item['description'],
                        'keyword'           => $keyword,
                        'latitude'          => null,
                        'longitude'         => null,
                        'raw_data'          => json_encode($item),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];

                    $dataReturn[] = [
                        'place_id'          => $item['place_id'],
                        'formatted_address' => $item['description'],
                    ];
                }

                ProcessCachingPlaceJob::dispatch($placesToCache);
            }

            return ServiceReturn::success($dataReturn);
        } catch (ConnectionException $e) {
            LogHelper::error("Lỗi LocationService@autoComplete", ex: $e);
            return ServiceReturn::error(__('common_error.goong_error'));
        } catch (ServiceException $e) {
            LogHelper::error("Lỗi LocationService@autoComplete", ex: $e);
            return ServiceReturn::error($e->getMessage());
        } catch (\Exception $e) {
            LogHelper::error("Lỗi LocationService@autoComplete", ex: $e);
            return ServiceReturn::error(__('common_error.goong_error'));
        }
    }

    public function processCachingPlace(?array $places)
    {
        try {
            if (empty($places)) {
                return ServiceReturn::success();
            }
            $updateColumn = [
                'formatted_address',
                'keyword',
                'raw_data',
                'updated_at',
                'latitude',
                'longitude',
            ];
            $current = now();

            $preparedPlaces = collect($places)->map(function ($place) use ($current) {
                $place['updated_at'] = $current;
                $place['created_at'] = $place['created_at'] ?? $current;
                $place['raw_data'] = isset($place['raw_data'])
                    ? (is_array($place['raw_data']) ? json_encode($place['raw_data']) : $place['raw_data'])
                    : null;

                return $place;
            })->toArray();
            $this->geoCachingPlaceRepository->query()->upsert($preparedPlaces, 'place_id', $updateColumn);

            return ServiceReturn::success();
        } catch (\Exception $e) {
            LogHelper::error(
                message: "Lỗi LocationService@processCachingPlace",
                ex: $e
            );
            return ServiceReturn::error(__('common_error.goong_error'));
        }
    }
}
