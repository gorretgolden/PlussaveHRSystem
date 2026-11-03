<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\HrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LinkCrawlTest extends TestCase
{
    use RefreshDatabase;

    private const SEED_PATHS = [
        '/dashboard',
        '/employees',
        '/leave',
        '/leave/apply',
        '/approvals',
        '/admin/audit',
        '/admin/roles',
        '/password/change',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HrSeeder::class);

        foreach ([['fiona', 'finance'], ['allan', 'auditor']] as [$username, $role]) {
            $user = User::create([
                'name' => ucfirst($username).' '.ucfirst($role),
                'username' => $username,
                'email' => "{$username}@example.test",
                'role' => $role,
                'password' => Hash::make('PlusSave#2026'),
                'active' => true,
                'must_change_password' => false,
            ]);
            $user->assignRole($role);
        }
    }

    public static function roleProvider(): array
    {
        return collect(['brenda', 'ruth', 'daniel', 'ivan', 'fiona', 'allan'])
            ->mapWithKeys(fn ($username) => [$username => [$username]])
            ->all();
    }

    /**
     * Walk the app as one signed-in role: every page it can reach must answer, and
     * every link on those pages must open for that same role.
     */
    #[DataProvider('roleProvider')]
    public function test_every_link_a_role_sees_opens_for_that_role(string $username): void
    {
        $user = User::where('username', $username)->firstOrFail();
        $visited = [];
        $dead = [];
        $queue = array_fill_keys(self::SEED_PATHS, null);

        while ($queue !== [] && count($visited) < 80) {
            $path = (string) key($queue);
            unset($queue[$path]);

            if (isset($visited[$path])) {
                continue;
            }

            $response = $this->actingAs($user)->get($path);
            $visited[$path] = $response->status();

            $this->assertLessThan(
                500,
                $response->status(),
                "[$username] {$path} returned {$response->status()} (server error)."
            );

            if ($response->status() !== 200) {
                continue;
            }

            foreach ($this->linksIn($response->getContent()) as $href) {
                if (! isset($visited[$href])) {
                    $queue[$href] = null;
                }
            }
        }

        $this->assertGreaterThan(6, count($visited), "[$username] crawl only reached ".count($visited).' pages, so this test is not walking the app.');

        foreach ($visited as $path => $status) {
            if (in_array($status, [403, 404], true) && ! in_array($path, self::SEED_PATHS, true)) {
                $dead[] = "{$path} ({$status})";
            }
        }

        $this->assertSame(
            [],
            $dead,
            "[$username] links point at pages this role cannot open: ".implode(', ', $dead)
        );
    }

    private function linksIn(string $html): array
    {
        preg_match_all('/href="([^"]+)"/', $html, $matches);

        $paths = [];
        foreach ($matches[1] as $href) {
            $href = html_entity_decode($href, ENT_QUOTES);

            if (! str_starts_with($href, 'http')) {
                continue; // assets and anchors; every app link Blade writes is absolute
            }

            if (! str_contains($href, '://localhost:8000')) {
                continue; // external site
            }

            $parts = parse_url($href);
            $query = [];
            parse_str($parts['query'] ?? '', $query);
            unset($query['page']); // paging widens the crawl without finding new bugs

            $normalized = '/'.ltrim($parts['path'] ?? '', '/');
            if ($normalized === '/logout' || str_starts_with($normalized, '/storage') || str_starts_with($normalized, '/build')) {
                continue;
            }

            $paths[$normalized.(empty($query) ? '' : '?'.http_build_query($query))] = null;
        }

        return array_keys($paths);
    }
}
