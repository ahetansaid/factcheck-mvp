<?php

namespace Database\Seeders;

use App\Models\Personality;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Rédacteur / admin pour se connecter à l'administration Filament.
        $admin = User::firstOrCreate(
            ['email' => 'admin@verifon.bj'],
            ['name' => 'Rédaction Vérifon', 'password' => Hash::make('verifon2026')]
        );

        $sante = Personality::firstOrCreate(
            ['slug' => 'rumeur-sante'],
            ['name' => 'Rumeur — Santé publique', 'role' => 'Désinformation sanitaire']
        );

        $verifs = [
            [
                'title' => "Non, une tisane ne guérit pas le paludisme",
                'claim' => "Boire une tisane suffit à guérir le paludisme.",
                'rating' => 'false',
                'category' => 'Santé',
                'personality_id' => $sante->id,
                'summary' => "Aucune tisane ne traite le paludisme, qui exige un traitement antipaludique confirmé. Se soigner uniquement par des infusions retarde la prise en charge et peut être mortel.",
                'body' => "Une rumeur récurrente affirme qu'une simple infusion suffirait à soigner le paludisme. C'est faux. Le paludisme est causé par un parasite et nécessite un traitement antipaludique validé. Les autorités sanitaires rappellent qu'un retard de prise en charge, surtout chez l'enfant et la femme enceinte, peut être fatal.",
                'sources' => [
                    ['title' => "OMS — Paludisme", 'url' => 'https://www.who.int/fr/news-room/fact-sheets/detail/malaria'],
                ],
            ],
            [
                'title' => "Cette photo de « manifestation à Cotonou » est sortie de son contexte",
                'claim' => "Une photo montre une manifestation à Cotonou cette semaine.",
                'rating' => 'misleading',
                'category' => 'Image',
                'personality_id' => null,
                'summary' => "L'image est authentique mais sortie de son contexte : elle date de 2019 et a été prise dans un autre pays. Rien n'indique une manifestation récente à Cotonou.",
                'body' => "La recherche d'image inversée montre que ce cliché circule depuis 2019 et a été pris hors du Bénin. Le republier comme une actualité récente relève de la désinformation par recontextualisation.",
                'sources' => [
                    ['title' => "Recherche d'image inversée", 'url' => 'https://images.google.com/'],
                ],
            ],
            [
                'title' => "Non, on ne pourra pas voter par téléphone à la prochaine élection",
                'claim' => "Le vote par téléphone est autorisé à la prochaine élection.",
                'rating' => 'false',
                'category' => 'Gouvernance',
                'personality_id' => null,
                'summary' => "Aucun dispositif de vote par téléphone n'existe. Le vote se fait en personne, au bureau de vote, avec la carte d'électeur. Toute annonce contraire est une rumeur.",
                'body' => "Le cadre électoral ne prévoit pas de vote à distance par téléphone. Ce type de rumeur vise à semer la confusion sur les modalités de vote. Renseignez-vous uniquement auprès des canaux officiels de l'institution électorale.",
                'sources' => [
                    ['title' => "Institution électorale — modalités de vote", 'url' => 'https://www.gouv.bj/'],
                ],
            ],
        ];

        foreach ($verifs as $data) {
            $sources = $data['sources'];
            unset($data['sources']);

            $v = Verification::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($data['title'])],
                $data + [
                    'author_id' => $admin->id,
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );

            foreach ($sources as $s) {
                $v->sources()->firstOrCreate(['url' => $s['url']], $s);
            }
        }
    }
}
