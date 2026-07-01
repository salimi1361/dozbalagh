import sys
import os

def update_marker(marker_name, new_code_content):
    file_path = "resources/views/dashboard.blade.php"
    if not os.path.exists(file_path):
        print(f"❌ خطا: فایل {file_path} پیدا نشد!")
        return False
        
    start_marker = f""
    end_marker = f""
    
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
        
    if start_marker not in content or end_marker not in content:
        print(f"❌ خطا: مارکر {marker_name} در فایل پیدا نشد!")
        return False
        
    # تفکیک و جایگزینی محتوای بین دو مارکر
    start_idx = content.find(start_marker) + len(start_marker)
    end_idx = content.find(end_marker)
    
    new_content = content[:start_idx] + "\n" + new_code_content + "\n" + content[end_idx:]
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
        
    print(f"🚀 مارکر {marker_name} با موفقیت و در کسری از ثانیه آپدیت شد!")
    # تخلیه خودکار کش لاراول پس از هر تزریق
    os.system("php artisan view:clear && php artisan cache:clear")
    return True

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python3 update_view.py MARKER_NAME 'NEW_CODE'")
        sys.exit(1)
    update_marker(sys.argv[1], sys.argv[2])
