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
            return "No momento não há apartamentos cadastrados na plataforma.\n\nSe quiser, posso te ajudar a cadastrar novos imóveis ou te mostrar outro tipo de anúncio.";
        }

        if (!$bestMatch) {
            $bestMatch = $properties[0];
        }

        $title = $bestMatch['title'] ?? 'Apartamento sem título';
        $price = isset($bestMatch['price']) ? number_format((float) $bestMatch['price'], 2, ',', '.') : '0,00';
        $location = $bestMatch['location'] ?? 'Localização não informada';
        $description = trim((string) ($bestMatch['description'] ?? ''));
        $ownerHint = isset($bestMatch['user_id']) ? 'Anúncio cadastrado por um locador da plataforma.' : '';

        $message = "🏠 *Imóvel que mais combina com sua busca:*\n\n";
        $message .= "*{$title}*\n";
        $message .= "💰 R$ {$price}\n";
        $message .= "📍 {$location}\n";

        if ($description !== '') {
            $message .= '📝 ' . mb_substr($description, 0, 180) . "\n";
        }

        if ($ownerHint !== '') {
            $message .= "\n{$ownerHint}\n";
        }

        $message .= "\nSe quiser, eu posso refinar a busca por bairro, faixa de preço ou número de quartos.";

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
