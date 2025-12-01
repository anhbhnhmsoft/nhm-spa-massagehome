<?php

namespace App\Core;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    /**
     * Model mà Repository sẽ tương tác.
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->setModel();
    }

    /**
     * Khởi tạo (resolve) Model từ Service Container.
     * @throws BindingResolutionException
     */
    private function setModel(): void
    {
        $this->model = app()->make($this->getModel());
    }
    /**
     * Lấy class Model mà Repository này sẽ làm việc.
     * CÁC REPOSITORY CON BẮT BUỘC PHẢI TRIỂN KHAI (implement) PHƯƠNG THỨC NÀY.
     * @return string
     */
    abstract public function getModel(): string;

    /**
     * BẮT BUỘC: Áp dụng filter vào query.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    abstract public function filterQuery(Builder $query, array $filters): Builder;

    /**
     * BẮT BUỘC: Áp dụng sort vào query.
     *
     * @param Builder $query
     * @param string|null $sortBy
     * @param string $direction
     * @return Builder
     */
    abstract public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder;



    /**
     * Lấy query builder của Model.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->model->query();
    }

    /**
     * Lấy tất cả bản ghi.
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Tạo một bản ghi mới.
     */
    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    /**
     * Tìm bản ghi bằng ID.
     */
    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * Tìm bản ghi bằng ID, nếu không thấy sẽ ném Exception.
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Cập nhật bản ghi bằng ID.
     */
    public function update(int|string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record;
    }

    /**
     * Xóa bản ghi bằng ID.
     */
    public function delete(int|string $id): bool
    {
        $record = $this->findOrFail($id);
        return $record->delete();
    }
}
