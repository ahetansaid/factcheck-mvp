<?php

namespace Tests\Feature;

use App\Models\Verification;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimReviewApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_liste_ne_renvoie_que_le_publie(): void
    {
        $this->getJson('/api/verifications')
            ->assertOk()
            ->assertJsonStructure(['data' => [['title', 'slug', 'rating', 'rating_label', 'summary']]]);
    }

    public function test_le_detail_contient_un_claimreview_valide(): void
    {
        $slug = Verification::published()->first()->slug;

        $this->getJson("/api/verifications/{$slug}")
            ->assertOk()
            ->assertJsonPath('data.claim_review.@graph.0.@type', 'ClaimReview')
            ->assertJsonPath('data.claim_review.@graph.0.reviewRating.bestRating', 5)
            ->assertJsonPath('data.claim_review.@graph.0.reviewRating.worstRating', 1);
    }

    public function test_recherche_par_mot_cle(): void
    {
        $this->getJson('/api/verifications?q=paludisme')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_le_bot_cite_ou_transmet_sans_inventer(): void
    {
        // Dans la base → verdict cité.
        $this->postJson('/api/ask', ['question' => 'une tisane guerit-elle le paludisme'])
            ->assertOk()
            ->assertJsonPath('matched', true);

        // Hors base → jamais de verdict inventé, transmission rédaction.
        $this->postJson('/api/ask', ['question' => 'la terre est-elle plate'])
            ->assertOk()
            ->assertJsonPath('matched', false);
    }
}
