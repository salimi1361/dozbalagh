import sys
import os

def apply_patch(marker_name, patch_file):
    target_path = "resources/views/dashboard.blade.php"
    if not os.path.exists(target_path):
        print(f"❌ خطا: فایل مقصد پیدا نشد!")
        return
    if not os.path.exists(patch_file):
        print(f"❌ خطا: فایل پچ {patch_file} پیدا نشد!")
        return

    with open(patch_file, 'r', encoding='utf-8') as f:
        new_code = f.read().strip()

    with open(target_path, 'r', encoding='utf-8') as f:
        content = f.read()

    start_marker = f""
    end_marker = f""

    if start_marker not in content or end_marker not in content:
        print(f"❌ خطا: مارکر {marker_name} در فایل مقصد یافت نشد!")
        return

    start_idx = content.find(start_marker) + len(start_marker)
    end_idx = content.find(end_marker)

    updated_content = content[:start_idx] + "\n" + new_code + "\n" + content[end_idx:]

    with open(target_path, 'w', encoding='utf-8') as f:
        f.write(updated_content)

    print(f"✅ مارکر {marker_name} با موفقیت تزریق و پچ شد.")
    os.system("php artisan view:clear && php artisan cache:clear")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python3 patch_view.py MARKER_NAME patch_file.txt")
        sys.exit(1)
    apply_patch(sys.argv[1], sys.argv[2])
