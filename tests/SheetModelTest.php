<?php

namespace Tests;

use Tests\Models\BrokenModel;
use Tests\Models\DefineHeadersModel;
use Tests\Models\InferredIdModel;
use Tests\Models\TestModel;
use function MongoDB\BSON\toJSON;

class SheetModelTest extends TestCase
{
    /**
     * @var TestModel
     */
    private $sheet;
    /**
     * @var string
     */
    private $cachePath;

    public function setUp(): void
    {
        parent::setUp();
        config(['sushi.cache-path' => __DIR__.'/cache']);
    }

    public function tearDown(): void
    {
        $this->clearCacheDirectory();
        parent::tearDown();
    }

    private function clearCacheDirectory()
    {
        array_map('unlink', glob(config('sushi.cache-path').'/*'));
    }

    /** @test */
    public function can_infer_id_from_row()
    {
        $sheet = InferredIdModel::all();
        $this->assertEquals('[{"name":"Ed","email":"ed@gros.co","id":1},{"name":"Justine","email":"justine@gros.co","id":2},{"name":"Bob","email":"","id":3},{"name":"Daniel","email":"daniel@gros.co","id":4},{"name":"Milo","email":"milo@gros.co","id":5}]', $sheet->toJson());
    }

    /** @test */
    public function will_bail_out_without_creating_cache_file_if_error_reading_sheet()
    {
        $this->assertFileDoesNotExist('tests/cache/sushi-tests-models-broken-model.sqlite');
        $this->expectException('Google_Service_Exception');
        $sheet = BrokenModel::all();
        $this->assertFileDoesNotExist('tests/cache/sushi-tests-models-broken-model.sqlite');
    }

    /** @test */
    public function can_read_from_google_sheets()
    {
        $this->clearCacheDirectory();
        $this->assertFileDoesNotExist('tests/cache/sushi-tests-models-test-model.sqlite');
        $sheet = new TestModel();
        $this->assertIsArray($sheet->getRows());
    }

    /** @test */
    public function does_not_hit_google_sheets_if_cache_exists()
    {
        $sheet = new TestModel();
        $this->assertFileExists('tests/cache/sushi-tests-models-test-model.sqlite');
        $this->assertStringContainsString(
            'tests/cache/sushi-tests-models-test-model.sqlite',
            $sheet->getConnection()->getDatabaseName()
        );
    }

    /** @test */
    public function can_do_basic_eloquent_stuff()
    {
        $sheet = TestModel::find(1);
        $this->assertEquals('Ed', $sheet->name);

        $sheet = TestModel::where('email', 'ed@gros.co')->first();
        $this->assertEquals(1, $sheet->id);

        $sheet = TestModel::where('name', 'Milo')->first();
        $this->assertEquals('Kid', $sheet->title);
    }

    /** @test */
    public function can_use_defined_headers()
    {
        $sheet = DefineHeadersModel::find(1);

        $this->assertEquals('Ed', $sheet->name);
    }

    /** @test */
    public function can_invalidate_cache()
    {
        $sheet = TestModel::find(1);
        $this->assertFileExists('tests/cache/sushi-tests-models-test-model.sqlite');
        $sheet->invalidateCache();
        $this->assertFileDoesNotExist('tests/cache/sushi-tests-models-test-model.sqlite');
        $sheet = TestModel::find(2);
        $this->assertEquals('Justine', $sheet->name);
    }

    /** @test */
    public function can_invalidate_cache_by_request()
    {
        $sheet = TestModel::find(1);
        $this->assertFileExists('tests/cache/sushi-tests-models-test-model.sqlite');
        $response = $this->get('/eloquent_sheets_forget/'.$sheet->cacheName);
        $response->assertSuccessful();
        $this->assertFileDoesNotExist('tests/cache/sushi-tests-test-model.sqlite');
    }
}
