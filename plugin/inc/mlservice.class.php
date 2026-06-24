<?php

class PluginUvtcampusfixMlservice {

    private static $endpoint = 'http://ml-service:8000';
    private static $timeout  = 3;

    static function classify(string $description): array {
        $fallback = [
            'category'     => 'Necunoscut',
            'confidence'   => 0.0,
            'ai_available' => false,
        ];

        if (empty(trim($description))) {
            return $fallback;
        }

        $url  = self::$endpoint . '/classify';
        $data = json_encode(['description' => $description]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CONNECTTIMEOUT => self::$timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200 || !$response) {
            return $fallback;
        }

        $result = json_decode($response, true);
        if (!$result || !isset($result['category'])) {
            return $fallback;
        }

        return [
            'category'     => $result['category'],
            'confidence'   => round((float)($result['confidence'] ?? 0), 2),
            'ai_available' => (bool)($result['ai_available'] ?? false),
        ];
    }

    static function health(): array {
        $ch = curl_init(self::$endpoint . '/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CONNECTTIMEOUT => self::$timeout,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return ['status' => 'unavailable', 'model_loaded' => false];
        }

        return json_decode($response, true) ?: ['status' => 'error', 'model_loaded' => false];
    }

    static function sendFeedback(string $description, string $aiCategory, string $userCategory, float $confidence, int $ticketId): void {
        $url  = self::$endpoint . '/feedback';
        $data = json_encode([
            'description'   => $description,
            'ai_category'   => $aiCategory,
            'user_category' => $userCategory,
            'confidence'    => $confidence,
            'ticket_id'     => $ticketId,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    static function feedbackStats(): array {
        $ch = curl_init(self::$endpoint . '/feedback/stats');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CONNECTTIMEOUT => self::$timeout,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return ['total' => 0, 'correct' => 0, 'accuracy' => 0.0, 'per_category' => []];
        }

        return json_decode($response, true) ?: ['total' => 0, 'correct' => 0, 'accuracy' => 0.0, 'per_category' => []];
    }

    static function getCategoryLabel(string $category): string {
        $labels = [
            'IT'            => 'Echipament IT (PC, Proiector, Monitor)',
            'Retea'         => 'Rețea (Wi-Fi, Internet, Conectivitate)',
            'Administrativ' => 'Administrativ (Mobilier, Iluminat, Încălzire)',
            'Necunoscut'    => 'Necunoscut — alege manual',
        ];
        return $labels[$category] ?? $category;
    }

    static function getConfidenceLevel(float $confidence): string {
        if ($confidence >= 0.70) return 'high';
        if ($confidence >= 0.50) return 'medium';
        return 'low';
    }
}
