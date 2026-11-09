<?php

namespace App\Services;

class AssistantFaqService
{
    /**
     * @return array{answer: string, matched: bool, question: ?string, suggestions: list<string>}
     */
    public function answer(string $question): array
    {
        $normalizedQuestion = $this->normalize($question);
        $bestFaq = null;
        $bestScore = 0;

        foreach (config('assistant.faqs', []) as $faq) {
            foreach ($faq['keywords'] as $keyword) {
                $normalizedKeyword = $this->normalize($keyword);
                if ($normalizedKeyword === '' || !str_contains($normalizedQuestion, $normalizedKeyword)) {
                    continue;
                }

                $score = mb_strlen($normalizedKeyword)
                    + ($normalizedQuestion === $normalizedKeyword ? 1000 : 0);
                if ($score > $bestScore) {
                    $bestFaq = $faq;
                    $bestScore = $score;
                }
            }
        }

        if ($bestFaq !== null) {
            return [
                'answer' => $bestFaq['answer'],
                'matched' => true,
                'question' => $bestFaq['question'],
                'suggestions' => array_values(array_slice(
                    $bestFaq['suggestions'] ?? config('assistant.suggestions', []),
                    0,
                    4
                )),
            ];
        }

        return [
            'answer' => 'مش لاقي إجابة مؤكدة لسؤالك في دليل المنصة. تقدر تختار سؤالًا مقترحًا أو تبعت سؤالك لفريق الدعم لمراجعته.',
            'matched' => false,
            'question' => null,
            'suggestions' => array_values(array_slice(config('assistant.suggestions', []), 0, 4)),
        ];
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
            'ـ' => '',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);
        $normalized = preg_replace('/[\p{Mn}\p{P}\p{S}\s]+/u', '', $text);

        return is_string($normalized) ? $normalized : '';
    }
}
