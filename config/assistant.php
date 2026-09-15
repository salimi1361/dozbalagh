<?php

use App\Services\Assistant\KeywordAnswerProvider;

return [
    'provider' => KeywordAnswerProvider::class,

    'knowledge_path' => resource_path('assistant/knowledge.php'),

    'supported_roles' => [
        'admin',
        'association',
        'company',
    ],

    'fallback_message' => 'پاسخ دقیق این موضوع در راهنمای فعلی ثبت نشده است. لطفاً با پشتیبانی سامانه تماس بگیرید.',

    'max_message_length' => 1000,
];
