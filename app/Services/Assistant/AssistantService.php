<?php

namespace App\Services\Assistant;

use InvalidArgumentException;
use RuntimeException;

class AssistantService
{
    public function __construct(
        private readonly CompanyAssistantDataService $companyData,
    ) {
    }

    public function answer(string $message, AssistantContext $context): string
    {
        $message = trim($message);
        $maxLength = (int) config('assistant.max_message_length', 1000);

        if ($message === '' || mb_strlen($message) > $maxLength) {
            throw new InvalidArgumentException('متن پرسش خالی یا بیش از حد مجاز است.');
        }

        $dataAnswer = $this->companyData->answer($message, $context);

        if (is_string($dataAnswer) && $dataAnswer !== '') {
            return $dataAnswer;
        }

        $providerClass = config('assistant.provider', KeywordAnswerProvider::class);

        if (! is_string($providerClass) || ! class_exists($providerClass)) {
            throw new RuntimeException('ارائه‌دهنده پاسخ دستیار معتبر نیست.');
        }

        $provider = app($providerClass);

        if (! method_exists($provider, 'answer')) {
            throw new RuntimeException('ارائه‌دهنده پاسخ دستیار قرارداد معتبر ندارد.');
        }

        $answer = $provider->answer($message, $context);

        return is_string($answer) && $answer !== ''
            ? $answer
            : (string) config('assistant.fallback_message');
    }
}
