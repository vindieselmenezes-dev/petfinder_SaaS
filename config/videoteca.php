<?php

declare(strict_types=1);

/**
 * ==========================================================
 * ECOSISTEMPET
 * Config: Videoteca Educativa (página public/videoteca.php)
 *
 * Os VÍDEOS ficam em assets/data/videos.json, a mesma lista do
 * baralho da home: cadastre ali uma vez e aparece nos dois lugares.
 *
 * Aqui ficam só as CATEGORIAS (abas da página). Para um vídeo entrar
 * numa aba, acrescente "categoria": "saude" (por exemplo) ao item no
 * videos.json. Sem categoria, ele aparece na aba "Geral".
 * ==========================================================
 */

return [
    'categorias' => [
        'saude'         => 'Saúde e vacinas',
        'alimentacao'   => 'Alimentação',
        'comportamento' => 'Comportamento e adestramento',
        'passeios'      => 'Viagens e passeios',
        'adocao'        => 'Adoção e ONGs',
    ],
];
