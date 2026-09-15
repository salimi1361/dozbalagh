<?php

namespace App\Services\Assistant;

use RuntimeException;

class KeywordAnswerProvider
{
    /**
     * @param  array<string, mixed>|null  $knowledge
     */
    public function __construct(private ?array $knowledge = null)
    {
    }

    public function answer(string $message, AssistantContext $context): ?string
    {
        $normalizedMessage = $this->normalize($message);
        $bestAnswer = null;
        $bestScore = 0;

        foreach ($this->knowledge() as $topic) {
            if (! is_array($topic) || ! $this->isAllowedForRole($topic, $context->role)) {
                continue;
            }

            $score = $this->keywordScore($normalizedMessage, $topic['keywords'] ?? []);

            if ($score <= $bestScore) {
                continue;
            }

            $answer = $topic['answers'][$context->role] ?? $topic['answer'] ?? null;

            if (is_string($answer) && $answer !== '') {
                $bestScore = $score;
                $bestAnswer = $answer;
            }
        }

        return $bestAnswer;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function knowledge(): array
    {
        if ($this->knowledge !== null) {
            return $this->knowledge;
        }

        $path = config('assistant.knowledge_path');

        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('فایل دانش دستیار در دسترس نیست.');
        }

        $knowledge = require $path;

        if (! is_array($knowledge)) {
            throw new RuntimeException('ساختار فایل دانش دستیار معتبر نیست.');
        }

        return $this->knowledge = $knowledge;
    }

    /**
     * @param  array<string, mixed>  $topic
     */
    private function isAllowedForRole(array $topic, string $role): bool
    {
        $roles = $topic['roles'] ?? [];

        return is_array($roles) && in_array($role, $roles, true);
    }

    /**
     * @param  mixed  $keywords
     */
    private function keywordScore(string $message, mixed $keywords): int
    {
        if (! is_array($keywords)) {
            return 0;
        }

        $score = 0;
        $messageTokens = $this->tokens($message);

        foreach ($keywords as $keyword) {
            if (! is_string($keyword)) {
                continue;
            }

            $normalizedKeyword = $this->normalize($keyword);

            if (str_contains($message, $normalizedKeyword)) {
                $score = max($score, 100 + mb_strlen($normalizedKeyword));
                continue;
            }

            $keywordTokens = $this->tokens($normalizedKeyword);

            if (
                count($keywordTokens) > 1
                && count(array_diff($keywordTokens, $messageTokens)) === 0
            ) {
                $score = max(
                    $score,
                    50 + array_sum(array_map('mb_strlen', $keywordTokens)),
                );
            }
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $value): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $this->normalize($value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(
            $tokens ?: [],
            static fn (string $token): bool => mb_strlen($token) > 1,
        )));
    }

    private function normalize(string $value): string
    {
        $value = str_replace(
            ['ي', 'ى', 'ك', "\u{200C}", "\u{200F}", "\u{200E}"],
            ['ی', 'ی', 'ک', ' ', '', ''],
            $value,
        );

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }
}
