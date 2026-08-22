<?php

namespace App\Http\Services;

class PromptInjectionDetector
{
    /**
     * Known prompt injection patterns with their risk scores.
     */
    protected array $patterns = [
        // System prompt extraction attempts
        '/ignore\s+(all\s+)?(previous|prior|above)\s+(instructions|rules|prompts)/iu' => 10,
        '/repeat\s+(your|the)\s+(system\s+)?(prompt|instructions|rules)/iu' => 10,
        '/what\s+(are|is)\s+your\s+(system\s+)?(prompt|instructions|rules)/iu' => 8,
        '/output\s+(everything|all|the\s+text)\s+(above|before)/iu' => 9,
        '/show\s+(me\s+)?(your|the)\s+(system\s+)?(prompt|instructions|rules|configuration)/iu' => 8,
        '/print\s+(your|the)\s+(system\s+)?(prompt|instructions)/iu' => 9,
        '/reveal\s+(your|the)\s+(system\s+)?(prompt|instructions|rules)/iu' => 9,
        '/display\s+(your|the)\s+(system\s+)?(prompt|instructions)/iu' => 9,
        '/dump\s+(your|the)\s+(system\s+)?(prompt|instructions|config)/iu' => 9,
        '/tell\s+me\s+(your|the)\s+(system\s+)?(prompt|instructions|rules)/iu' => 8,
        '/copy\s+(your|the)\s+(system\s+)?(prompt|instructions)/iu' => 9,
        '/leak\s+(your|the)\s+(system\s+)?(prompt|instructions)/iu' => 10,
        '/translate\s+(your|the)\s+(system\s+)?(prompt|instructions)\s+to/iu' => 9,
        '/summarize\s+(your|the)\s+(system\s+)?(prompt|instructions)/iu' => 9,
        '/paraphrase\s+(your|the)\s+(system\s+)?(prompt|instructions)/iu' => 9,
        '/what\s+were\s+you\s+told/iu' => 7,
        '/what\s+is\s+your\s+(initial|first|original)\s+message/iu' => 8,

        // Arabic prompt injection patterns
        '/تجاهل\s+(كل\s+)?التعليمات/iu' => 10,
        '/اعرض\s+(لي\s+)?التعليمات/iu' => 8,
        '/ما\s+هي\s+تعليماتك/iu' => 8,
        '/اظهر\s+(لي\s+)?(البرومبت|التعليمات|الأوامر)/iu' => 9,
        '/انسى\s+(كل\s+)?(التعليمات|الأوامر|القواعد)/iu' => 10,
        '/قلي\s+(ايه|إيه)\s+(التعليمات|الأوامر|البرومبت)/iu' => 8,
        '/ايه\s+(هي|هو)\s+(التعليمات|البرومبت|الأوامر)/iu' => 8,
        '/ورينى\s+(التعليمات|البرومبت|الأوامر)/iu' => 9,
        '/غير\s+(دورك|شخصيتك)/iu' => 8,
        '/انت\s+دلوقتي\s+(بقيت|هتكون)/iu' => 7,

        // Role manipulation
        '/you\s+are\s+now\s+(a|an|the)/iu' => 7,
        '/act\s+as\s+(a|an|if)/iu' => 6,
        '/pretend\s+(to\s+be|you\s+are)/iu' => 7,
        '/new\s+(persona|identity|role|character)/iu' => 7,
        '/from\s+now\s+on\s+(you|your|ignore)/iu' => 8,
        '/switch\s+to\s+(a\s+)?(new|different)\s+(mode|role|persona)/iu' => 7,
        '/forget\s+(everything|all|your)\s+(you|rules|instructions)/iu' => 9,
        '/reset\s+(your|all)\s+(instructions|rules|context)/iu' => 9,
        '/enter\s+(a\s+)?(new|different|special)\s+mode/iu' => 7,

        // Jailbreaking
        '/\bDAN\b/u' => 6,
        '/do\s+anything\s+now/iu' => 8,
        '/developer\s+mode/iu' => 7,
        '/no\s+restrictions/iu' => 6,
        '/jailbreak/iu' => 9,
        '/bypass\s+(your\s+)?(rules|restrictions|filters|safety)/iu' => 9,
        '/override\s+(your\s+)?(rules|restrictions|programming)/iu' => 9,
        '/god\s+mode/iu' => 8,
        '/admin\s+mode/iu' => 7,
        '/debug\s+mode/iu' => 7,
        '/maintenance\s+mode/iu' => 7,
        '/sudo\s+/iu' => 7,
        '/unfiltered\s+(mode|response|output)/iu' => 8,

        // Delimiter / format attacks
        '/\[SYSTEM\]/iu' => 8,
        '/\[INST\]/iu' => 8,
        '/<<SYS>>/u' => 9,
        '/<\|im_start\|>/u' => 9,
        '/ASSISTANT:/u' => 7,
        '/SYSTEM:/u' => 8,
        '/```system/iu' => 8,
        '/\[\/INST\]/u' => 8,
        '/<\|endoftext\|>/u' => 9,
        '/<\|assistant\|>/u' => 8,
        '/<\|user\|>/u' => 7,
        '/<\|system\|>/u' => 9,
        '/Human:/u' => 5,
        '/Assistant:/u' => 5,

        // Data exfiltration attempts
        '/give\s+me\s+(all\s+)?(user|customer|order|sale|email|password|database)/iu' => 9,
        '/list\s+(all\s+)?(users|customers|orders|emails|passwords)/iu' => 8,
        '/how\s+many\s+(users|customers|orders|sales)/iu' => 5,
        '/show\s+(me\s+)?(the\s+)?(database|server|backend|admin)/iu' => 8,
        '/what\s+(database|server|framework|technology)\s+do\s+you\s+use/iu' => 6,
        '/اديني\s+(كل\s+)?(بيانات|معلومات)\s+(العملاء|المستخدمين|الطلبات)/iu' => 9,
        '/عايز\s+(بيانات|معلومات)\s+(العملاء|المبيعات|الاوردرات)/iu' => 9,
    ];

    /**
     * Threshold for blocking a message.
     */
    protected int $blockThreshold = 7;

    /**
     * Analyze a message for prompt injection attempts.
     *
     * @return array{safe: bool, score: int, matched_patterns: array}
     */
    public function analyze(string $message): array
    {
        $score = 0;
        $matchedPatterns = [];

        foreach ($this->patterns as $pattern => $weight) {
            if (preg_match($pattern, $message)) {
                $score += $weight;
                $matchedPatterns[] = $pattern;
            }
        }

        return [
            'safe' => $score < $this->blockThreshold,
            'score' => $score,
            'matched_patterns' => $matchedPatterns,
        ];
    }
}
