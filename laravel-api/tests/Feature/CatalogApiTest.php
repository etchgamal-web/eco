<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $role = Role::query()->where('slug', 'admin')->firstOrFail();
        $this->user = User::factory()->create();
        $this->user->roles()->attach($role);
    }

    public function test_product_crud_and_missing_product_response(): void
    {
        $create = $this->actingAs($this->user)->postJson('/api/v1/products', ['name' => 'Phone', 'type' => 'simple', 'status' => 'active']);
        $create->assertCreated()->assertJsonPath('data.slug', 'phone');
        $id = $create->json('data.id');
        $this->actingAs($this->user)->getJson("/api/v1/products/{$id}")->assertOk();
        $this->actingAs($this->user)->patchJson("/api/v1/products/{$id}", ['name' => 'Smart Phone', 'type' => 'simple', 'status' => 'active'])->assertOk();
        $this->actingAs($this->user)->deleteJson("/api/v1/products/{$id}")->assertNoContent();
        $this->actingAs($this->user)->getJson("/api/v1/products/{$id}")->assertNotFound();
    }

    public function test_simple_product_rejects_variants_and_variable_product_enforces_uniqueness(): void
    {
        $a = $this->actingAs($this->user)->postJson('/api/v1/attributes', ['name' => 'Color'])->json('data.id');
        $v = $this->actingAs($this->user)->postJson("/api/v1/attributes/{$a}/values", ['value' => 'Red'])->json('data.id');
        $simple = $this->actingAs($this->user)->postJson('/api/v1/products', ['name' => 'Simple', 'type' => 'simple', 'status' => 'active'])->json('data.id');
        $payload = ['sku' => 'SKU-1', 'price' => 10, 'status' => 'active', 'attribute_value_ids' => [$v]];
        $this->actingAs($this->user)->postJson("/api/v1/products/{$simple}/variants", $payload)->assertConflict();
        $variable = $this->actingAs($this->user)->postJson('/api/v1/products', ['name' => 'Variable', 'type' => 'variable', 'status' => 'active'])->json('data.id');
        $this->actingAs($this->user)->postJson("/api/v1/products/{$variable}/variants", $payload)->assertCreated();
        $this->actingAs($this->user)->postJson("/api/v1/products/{$variable}/variants", array_merge($payload, ['sku' => 'SKU-2']))->assertConflict();
    }

    public function test_storefront_catalog_is_public_but_mutations_require_permissions(): void
    {
        $this->postJson('/api/v1/products', [])->assertUnauthorized();
        $this->getJson('/api/v1/products')->assertOk();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/products', [])->assertForbidden();
    }

    public function test_products_can_be_imported_from_csv_and_xlsx(): void
    {
        $csv = UploadedFile::fake()->createWithContent('products.csv', "name,type,status,slug\nCSV Product,simple,active,\nCSV Product,simple,active,\n");
        $this->actingAs($this->user)->post('/api/v1/products/import', ['file' => $csv])
            ->assertCreated()
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.rows', 2);

        $xlsx = UploadedFile::fake()->createWithContent('products.xlsx', $this->xlsx([
            ['name', 'type', 'status', 'slug'],
            ['XLSX Product', 'variable', 'draft', 'xlsx-product'],
        ]));
        $this->actingAs($this->user)->post('/api/v1/products/import', ['file' => $xlsx])
            ->assertCreated()
            ->assertJsonPath('data.created', 1);

        $this->assertDatabaseHas('products', ['slug' => 'csv-product']);
        $this->assertDatabaseHas('products', ['slug' => 'csv-product-2']);
        $this->assertDatabaseHas('products', ['slug' => 'xlsx-product', 'type' => 'variable']);
    }

    public function test_product_import_is_atomic_and_requires_product_creation_permission(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/products/import', ['file' => UploadedFile::fake()->create('products.csv', 1, 'text/csv')])
            ->assertUnauthorized();

        $file = UploadedFile::fake()->createWithContent('products.csv', "name,type,status,slug\nFirst,simple,active,first\nBroken,invalid,active,broken\n");
        $this->actingAs($this->user)->post('/api/v1/products/import', ['file' => $file])->assertConflict();
        $this->assertDatabaseMissing('products', ['slug' => 'first']);
    }

    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'products-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $xmlRows = [];
        foreach ($rows as $rowNumber => $row) {
            $cells = [];
            foreach (array_values($row) as $column => $value) {
                $reference = '';
                $number = $column + 1;
                while ($number > 0) {
                    $remainder = ($number - 1) % 26;
                    $reference = chr(65 + $remainder).$reference;
                    $number = intdiv($number - 1, 26);
                }
                $cells[] = '<c r="'.$reference.($rowNumber + 1).'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }
            $xmlRows[] = '<row r="'.($rowNumber + 1).'">'.implode('', $cells).'</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $xmlRows).'</sheetData></worksheet>');
        $zip->close();
        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }
}
