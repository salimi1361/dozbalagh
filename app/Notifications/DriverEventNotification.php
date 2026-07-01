namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class DriverEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $driver;
    protected $event;
    protected $dozbalaghItem;

    // دریافت اطلاعات راننده، رویداد و آیتم دوزبلاغ هنگام ایجاد اعلان
    public function __construct($driver, $event, $dozbalaghItem)
    {
        $this->driver = $driver;
        $this->event = $event;
        $this->dozbalaghItem = $dozbalaghItem;
    }

    // تعیین کانال‌های ارسال (دیتابیس برای فیلومنت و کانال سفارشی برای بله)
    public function via($notifiable)
    {
        return ['database', 'bale']; // کانال سوم یعنی 'sms' را هم بعداً می‌توان اضافه کرد
    }

    // ۱. ساختار ذخیره‌سازی در دیتابیس برای پنل فیلومنت شرکت
    public function toDatabase($notifiable)
    {
        $statusText = $this->getEventStatusText($this->event->event_type);
        $date = json_encode(now()->format('Y/m/d')); // فرمت تاریخ استاندارد مدنظر شما

        return [
            'title' => 'تغییر وضعیت دوزبلاغ',
            'message' => "راننده {$this->driver->name} وضعیت دوزبلاغ شماره {$this->dozbalaghItem->serial_number} را به «{$statusText}» تغییر داد.",
            'driver_id' => $this->driver->id,
            'dozbalagh_item_id' => $this->dozbalaghItem->id,
            'recorded_at' => $date,
        ];
    }

    // ۲. ارسال پیام به بازوی بله (Bale Bot) شرکت
    public function toBale($notifiable)
    {
        $statusText = $this->getEventStatusText($this->event->event_type);
        $time = now()->format('H:i');
        $date = now()->format('Y/m/d');

        // تبدیل اعداد انگلیسی به فارسی برای ظاهر شکیل‌تر و حرفه‌ای وب‌پورتال
        $message = "🔔 *اعلان سامانه دوزبلاغ*\n\n"
            . "👤 *راننده:* {$this->driver->name}\n"
            . "📄 *شماره دوزبلاغ:* " . $this->toPersianNumbers($this->dozbalaghItem->serial_number) . "\n"
            . "📍 *وضعیت جدید:* #{$statusText}\n"
            . "📅 *تاریخ:* " . $this->toPersianNumbers($date) . "\n"
            . "⏰ *ساعت:* " . $this->toPersianNumbers($time) . "\n\n"
            . "⚙️ بررسی جزئیات بیشتر در پنل مدیریت دوزبلاغ.";

        // در اینجا متد ارسال پیام بازوی بله شما صدا زده می‌شود
        // $notifiable در اینجا همان مدل Company است که باید متد یا فیلد bale_chat_id داشته باشد
        if ($notifiable->bale_chat_id) {
            // فرستادن درخواست HTTP به API بله (پروتکل بله مشابه تلگرام است)
            // Http::post("https://api.bale.ai/bot" . config('services.bale.token') . "/sendMessage", [...])
        }
    }

    // متد کمکی برای ترجمه وضعیت‌ها به متن فارسی
    private function getEventStatusText($eventType)
    {
        return match ($eventType) {
            'started_trip' => 'شروع حرکت راننده',
            'at_border_out' => 'استقرار در مرز خروجی',
            'in_transit' => 'در حال ترانزیت در کشور مقصد',
            'at_destination' => 'وصول به گمرک مقصد / پایان جاده',
            default => 'تغییر وضعیت سفر',
        };
    }

    // متد اختصاصی برای فارسی‌سازی اعداد بر اساس استاندارد پروژه شما
    private function toPersianNumbers($string)
    {
        $farsiDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latinDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($latinDigits, $farsiDigits, $string);
    }
}