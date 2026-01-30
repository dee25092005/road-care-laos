<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Inertia\Testing\AssertableInertia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
class ReportController extends TestCase
{
    use RefreshDatabase;
    use WithFaker;


    public function test_admin_index_method_returns_inertia_response_with_reports_data(): void
    {
       // Arrange
       $admin = User::factory()->create(['is_admin' => true]);
       $this->actingAs($admin);
       Report::factory(3)->create();

       // Act
       $response = $this->get(route('admin.dashboard'));

       // Assert
       $response->assertStatus(200);

       $response->assertInertia(function (AssertableInertia $page) {
           $page->component('Admin/Dashboard');
           $page->has('reports.data', 3);
           $page->has('pendingCount');
           $page->has('fixedCount');
           $page->has('filters');

           return true;
       });
    }

     
    public function test_update_status_method_updates_report_status_successfully()
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $report = Report::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->put(route('reports.updateStatus', $report), [
            'status' => 'fixed',
        ]);

        // Assert
        $response->assertStatus(302);
        $response->assertRedirectBack();
        $this->assertEquals('fixed', $report->fresh()->status);
    }

     /**
     * Helper to call unaccessible method
     * @param object $obj
     * @param string $name
     * @param array  $args
     * @return mixed
     */
    public function callPrivateMethod($obj, $name, array $args) {
        $class = new \ReflectionClass(get_class($obj));
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    public function index_displays_reports()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Report::factory()->count(2)->create();

        $response = $this->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Reports/Index')
                 ->has('reports')
        );
    }

    public function store_creates_report_with_images()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'title' => 'Test Report',
            'description' => 'Test Description',
            'latitude' => 1.23,
            'longitude' => 4.56,
            'images' => [
                UploadedFile::fake()->image('photo1.jpg'),
                UploadedFile::fake()->image('photo2.png'),
            ],
        ];

        $response = $this->post(route('reports.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'title' => 'Test Report',
            'description' => 'Test Description',
        ]);
        Storage::disk('public')->assertExists('reports/' . $data['images'][0]->hashName());
    }
}


