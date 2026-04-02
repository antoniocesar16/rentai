<?php

namespace App\Services;

class PropertyMatchService
{
    public function findBestMatchingProperty(array $properties, string $userMessage): ?array
    {
        if (empty($properties)) {
            return null;
        }

        $terms = $this->extractSearchTerms($userMessage);
        $bestMatch = null;
        $bestScore = -1;

        foreach ($properties as $property) {
            $score = $this->scorePropertyMatch($property, $terms);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $property;
            }
        }

        return $bestMatch;
    }

    public function buildBestApartmentMessage(?array $bestMatch, array $properties): string
    {
        if (empty($properties)) {
            return "No momento não encontrei apartamentos disponíveis aqui na plataforma. Se quiser, me diz o bairro e a faixa de preço que eu te aviso assim que aparecer uma opção boa para você.";
        }

        if (!$bestMatch) {
            $bestMatch = $properties[0];
        }

        $title = $bestMatch['title'] ?? 'Apartamento sem título';
        $price = isset($bestMatch['price']) ? number_format((float) $bestMatch['price'], 2, ',', '.') : '0,00';
        $location = $bestMatch['location'] ?? 'Localização não informada';
        $description = trim((string) ($bestMatch['description'] ?? ''));

        $message = "Encontrei uma opção que combina com o que você pediu.\n\n";
        $message .= "Temos um apartamento em *{$location}*:\n";
        $message .= "• *{$title}*\n";
        $message .= "• Valor: *R$ {$price}*\n";

        if ($description !== '') {
            $message .= '• Detalhes: ' . mb_substr($description, 0, 180) . "\n";
        }

        $message .= "\nSe quiser, eu te mostro mais opções parecidas ou já filtro por bairro, valor e quantidade de quartos.";

        return $message;
    }

    private function scorePropertyMatch(array $property, array $terms): int
    {
        $haystack = mb_strtolower(implode(' ', [
            (string) ($property['title'] ?? ''),
            (string) ($property['location'] ?? ''),
            (string) ($property['description'] ?? ''),
            json_encode($property['details'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));

        $score = 0;

        foreach ($terms as $term) {
            if ($term !== '' && str_contains($haystack, $term)) {
                $score += 3;
            }
        }

        if (empty($terms)) {
            $score += 1;
        }

        return $score;
    }

    private function extractSearchTerms(string $userMessage): array
    {
        $normalized = mb_strtolower(trim($userMessage));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
        $tokens = preg_split('/[^a-z0-9]+/', $normalized) ?: [];

        $ignored = [
            'a', 'o', 'os', 'as', 'de', 'da', 'do', 'das', 'dos', 'em', 'no', 'na', 'nos', 'nas',
            'tem', 'apartamento', 'apartamentos', 'imovel', 'imoveis', 'casa', 'casas',
            'quero', 'mostrar', 'listar', 'ver', 'encontrar', 'busca', 'buscar', 'procuro', 'lista',
        ];

        return array_values(array_filter($tokens, fn($token) => $token !== '' && !in_array($token, $ignored, true)));
    }
}
