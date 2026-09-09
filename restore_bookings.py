#!/usr/bin/env python3
import gzip
import json
import os
import re
import subprocess
import sys

# Tìm file backup
possible_paths = [
    "/root/backup_massagehome_20260828_175502.sql.gz",
    "backup_massagehome_20260828_175502.sql.gz",
    "../backup_massagehome_20260828_175502.sql.gz",
    "/home/duong/www/nhm-spa-massagehome-all/backup_massagehome_20260828_175502.sql.gz"
]

backup_path = None
for p in possible_paths:
    if os.path.exists(p):
        backup_path = p
        break

if not backup_path:
    print("❌ LỖI: Không tìm thấy file backup_massagehome_20260828_175502.sql.gz!")
    sys.exit(1)

print(f"📖 Đang đọc dữ liệu từ file backup: {backup_path}...")

existing_bids = {
    '163900445581240006', '165763047383340775', '166781137496484704', '167123755962482971',
    '167364898332132039', '167369180387815730', '168555627311686058', '168814688580183041',
    '169007617152084325', '170364780251091072', '170814542029919638', '171086788980099655',
    '171105010034816220', '173293481976120677', '173886194683070329', '173888344610145377',
    '175890240014383404', '175917193103827868', '175917303173788281', '175918265986127395',
    '179422806512361854', '179992810366562776', '180410625740484247', '184223769268209230'
}

users = {}
profiles = {}
review_apps = {}
categories = {}
bookings = []

curr_tbl = None
cols = []

with gzip.open(backup_path, 'rt', encoding='utf-8', errors='ignore') as f:
    for line in f:
        if line.startswith('COPY public.'):
            m = re.match(r'COPY public\.(\w+) \((.*?)\) FROM stdin;', line.strip())
            if m:
                curr_tbl = m.group(1)
                cols = [c.strip() for c in m.group(2).split(',')]
            else:
                curr_tbl = None
        elif line.startswith(r'\.'):
            curr_tbl = None
        elif curr_tbl:
            parts = line.rstrip('\r\n').split('\t')
            row = dict(zip(cols, parts))
            if curr_tbl == 'users':
                users[row['id']] = {'name': row.get('name'), 'phone': row.get('phone')}
            elif curr_tbl == 'user_profiles':
                profiles[row['user_id']] = {'avatar_url': row.get('avatar_url'), 'gender': row.get('gender')}
            elif curr_tbl == 'user_review_application':
                bio = row.get('bio')
                nickname = None
                if bio and bio != r'\N':
                    try:
                        nickname = json.loads(bio).get('nickname')
                    except Exception:
                        pass
                review_apps[row['user_id']] = {'nickname': nickname}
            elif curr_tbl == 'categories':
                raw_name = row.get('name')
                name = raw_name
                if raw_name and raw_name.startswith('{'):
                    try:
                        name_obj = json.loads(raw_name)
                        name = name_obj.get('vi') or name_obj.get('en') or list(name_obj.values())[0]
                    except Exception:
                        pass
                categories[row['id']] = {'name': name}
            elif curr_tbl == 'service_bookings':
                if row['id'] not in existing_bids:
                    bookings.append(row)

print(f"📦 Tìm thấy {len(bookings)} đơn hàng cần khôi phục.")

def esc(v):
    if v is None or v == r'\N':
        return 'NULL'
    return "'" + str(v).replace("'", "''") + "'"

out_file = "/tmp/restore_52_bookings_only.sql"
with open(out_file, 'w', encoding='utf-8') as f:
    f.write('-- KHÔI PHỤC 52 ĐƠN HÀNG LỊCH SỬ - KHÔNG TẠO LẠI KTV ĐÃ BỊ XÓA\n')
    f.write('BEGIN;\n\n')
    for b in bookings:
        kid = b.get('ktv_user_id')
        cid = b.get('user_id')
        catid = b.get('category_id')

        ktv_info = users.get(kid, {})
        ktv_rev = review_apps.get(kid, {})
        ktv_prof = profiles.get(kid, {})
        ktv_name = ktv_rev.get('nickname') or ktv_info.get('name')
        ktv_phone = ktv_info.get('phone')
        ktv_avatar = ktv_prof.get('avatar_url')

        cust_info = users.get(cid, {})
        cust_prof = profiles.get(cid, {})
        cust_name = cust_info.get('name')
        cust_phone = cust_info.get('phone')
        cust_avatar = cust_prof.get('avatar_url')
        cust_gender = cust_prof.get('gender')

        cat_info = categories.get(catid, {})
        serv_name = cat_info.get('name')

        cols = [
            'id', 'user_id', 'ktv_user_id', 'category_id', 'coupon_id', 'duration',
            'booking_time', 'start_time', 'end_time', 'status', 'price', 'price_discount',
            'price_transportation', 'payment_type', 'note', 'address', 'latitude', 'longitude',
            'ktv_address', 'ktv_latitude', 'ktv_longitude', 'reason_cancel', 'cancel_by',
            'created_at', 'updated_at', 'ktv_confirm_deadline_at', 'application_opened_at',
            'application_open_reason',
            # Snapshot columns
            'ktv_name', 'ktv_phone', 'ktv_avatar_url',
            'customer_name', 'customer_phone', 'customer_avatar_url', 'customer_gender',
            'service_name'
        ]

        vals = [
            esc(b['id']),
            esc(b['user_id']),
            'NULL',  # Để NULL để KHÔNG cần tạo lại KTV đã bị xóa trong bảng users
            esc(b['category_id']),
            esc(b['coupon_id']),
            esc(b['duration']),
            esc(b['booking_time']),
            esc(b['start_time']),
            esc(b['end_time']),
            esc(b['status']),
            esc(b['price']),
            esc(b['price_discount']),
            esc(b['price_transportation']),
            esc(b['payment_type']),
            esc(b['note']),
            esc(b['address']),
            esc(b['latitude']),
            esc(b['longitude']),
            esc(b['ktv_address']),
            esc(b['ktv_latitude']),
            esc(b['ktv_longitude']),
            esc(b['reason_cancel']),
            esc(b['cancel_by']),
            esc(b['created_at']),
            esc(b['updated_at']),
            esc(b.get('ktv_confirm_deadline_at')),
            esc(b.get('application_opened_at')),
            esc(b.get('application_open_reason')),
            # Snapshots
            esc(ktv_name),
            esc(ktv_phone),
            esc(ktv_avatar),
            esc(cust_name),
            esc(cust_phone),
            esc(cust_avatar),
            esc(cust_gender) if cust_gender and cust_gender != r'\N' else 'NULL',
            esc(serv_name)
        ]

        f.write(f"INSERT INTO public.service_bookings ({', '.join(cols)}) VALUES ({', '.join(vals)}) ON CONFLICT (id) DO NOTHING;\n")

    f.write('\nCOMMIT;\n')

print(f"⚙️ Đang thực thi nạp {len(bookings)} đơn hàng vào database 'massagehome_system'...")

res = subprocess.run([
    'sudo', '-u', 'postgres', 'psql', '-d', 'massagehome_system', '-f', out_file
], capture_output=True, text=True)

if res.returncode == 0:
    print("========================================================================")
    print("✨ THÀNH CÔNG RỒI Ạ!")
    print(f"👉 Đã khôi phục toàn bộ {len(bookings)} đơn hàng lịch sử với đầy đủ Snapshot!")
    print("👉 KHÔNG TẠO LẠI BẤT KỲ KTV NÀO ĐÃ XÓA (Tài khoản KTV cũ vẫn ở trạng thái đã xóa)!")
    print("========================================================================")
else:
    print("❌ LỖI KHI NẠP DỮ LIỆU:")
    print(res.stderr)
