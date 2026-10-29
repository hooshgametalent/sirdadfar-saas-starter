<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\DriverVerificationStatus;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Driver\MyJobs;
use App\Livewire\Driver\OpenOrders;
use App\Livewire\Driver\Profile;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Models\VehicleType;
use App\Services\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesFreightData;
use Tests\TestCase;

class DriverPanelTest extends TestCase
{
    use CreatesFreightData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->fakeSms();
    }

    public function test_driver_panel_pages_render(): void
    {
        $driver = $this->approvedDriver();

        $this->actingAs($driver);

        $this->get(route('driver.dashboard'))->assertOk()->assertSee('وضعیت تأیید حساب');
        $this->get(route('driver.profile'))->assertOk()->assertSee('اطلاعات خودرو');
        $this->get(route('driver.orders.open'))->assertOk();
        $this->get(route('driver.jobs'))->assertOk();
    }

    public function test_unverified_driver_cannot_see_open_orders_or_submit_offers(): void
    {
        $driver = $this->pendingDriver();
        $order = Order::factory()->openForOffers()->create();

        Livewire::actingAs($driver)
            ->test(OpenOrders::class)
            ->assertSee('حساب شما هنوز تأیید نشده است')
            ->assertDontSee($order->reference)
            ->call('startOffer', $order->id)
            ->assertForbidden();

        Livewire::actingAs($driver)
            ->test(OpenOrders::class)
            ->set('offerOrderId', $order->id)
            ->set('offerPrice', '25000000')
            ->call('submitOffer')
            ->assertForbidden();

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_verified_driver_sees_open_orders_without_shipper_contact_and_submits_an_offer(): void
    {
        $driver = $this->approvedDriver();
        $shipper = User::factory()->shipper()->create(['mobile' => '09003334455']);
        $order = Order::factory()->openForOffers()->create(['shipper_id' => $shipper->id, 'offered_price' => 28000000]);
        $pending = Order::factory()->create();

        $component = Livewire::actingAs($driver)
            ->test(OpenOrders::class)
            ->assertSee($order->reference)
            ->assertDontSee($pending->reference)
            ->assertDontSee('09003334455')
            ->assertDontSee($order->origin_address)
            ->call('startOffer', $order->id)
            ->assertSet('offerOrderId', $order->id)
            ->assertSet('offerPrice', '28000000')
            ->set('offerPrice', '۲۷۰۰۰۰۰۰')
            ->set('offerMessage', 'آماده بارگیری از صبح')
            ->call('submitOffer')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', null)
            ->assertSet('offerOrderId', null);

        $offer = Offer::query()->firstOrFail();

        $this->assertSame($driver->id, $offer->driver_id);
        $this->assertSame(27000000, $offer->price);
        $this->assertSame(OfferStatus::Pending, $offer->status);

        $component->call('startOffer', $order->id)
            ->call('submitOffer');

        $this->assertNotNull($component->get('errorMessage'));
        $this->assertSame(1, Offer::query()->count());
    }

    public function test_verified_driver_can_withdraw_own_pending_offer_only(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->openForOffers()->create();
        $offer = app(OfferService::class)->submit($driver, $order, 25000000);
        $foreign = Offer::factory()->create();

        Livewire::actingAs($driver)
            ->test(OpenOrders::class)
            ->call('withdrawOffer', $offer->id)
            ->assertSet('errorMessage', null);

        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);

        Livewire::actingAs($driver)
            ->test(OpenOrders::class)
            ->call('withdrawOffer', $foreign->id)
            ->assertNotFound();

        $this->assertSame(OfferStatus::Pending, $foreign->fresh()->status);
    }

    public function test_open_orders_can_be_filtered_by_vehicle_type(): void
    {
        $driver = $this->approvedDriver();
        $truck = VehicleType::factory()->create();
        $van = VehicleType::factory()->create();
        $truckOrder = Order::factory()->openForOffers()->create(['vehicle_type_id' => $truck->id]);
        $vanOrder = Order::factory()->openForOffers()->create(['vehicle_type_id' => $van->id]);

        Livewire::actingAs($driver)
            ->test(OpenOrders::class)
            ->set('vehicleTypeId', (string) $truck->id)
            ->assertSee($truckOrder->reference)
            ->assertDontSee($vanOrder->reference);
    }

    public function test_driver_can_create_a_profile_for_verification(): void
    {
        $driver = User::factory()->driver()->create();
        $vehicle = VehicleType::factory()->create();

        Livewire::actingAs($driver)
            ->test(Profile::class)
            ->call('saveProfile')
            ->assertHasErrors(['vehicle_type_id' => 'required', 'plate_number' => 'required'])
            ->set('vehicle_type_id', (string) $vehicle->id)
            ->set('plate_number', '۱۲ع۳۴۵-۶۷')
            ->set('capacity_kg', '۱۰۰۰۰')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $profile = DriverProfile::query()->where('user_id', $driver->id)->firstOrFail();

        $this->assertSame(DriverVerificationStatus::Pending, $profile->verification_status);
        $this->assertSame(10000, $profile->capacity_kg);
    }

    public function test_approved_profile_cannot_be_changed_by_the_driver(): void
    {
        $driver = $this->approvedDriver();
        $plate = $driver->driverProfile->plate_number;

        $component = Livewire::actingAs($driver)
            ->test(Profile::class)
            ->set('plate_number', 'پلاک جدید')
            ->call('saveProfile');

        $this->assertNotNull($component->get('errorMessage'));
        $this->assertSame($plate, $driver->driverProfile->fresh()->plate_number);
        $this->assertSame(DriverVerificationStatus::Approved, $driver->driverProfile->fresh()->verification_status);
    }

    public function test_document_upload_is_stored_on_the_private_disk_with_a_generated_name(): void
    {
        Storage::fake('local');

        $driver = $this->pendingDriver();
        $profile = $driver->driverProfile;

        Livewire::actingAs($driver)
            ->test(Profile::class)
            ->set('document_type', DocumentType::DrivingLicense->value)
            ->set('document', UploadedFile::fake()->create('license.pdf', 200, 'application/pdf'))
            ->call('uploadDocument')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', null);

        $document = DriverDocument::query()->firstOrFail();

        Storage::disk('local')->assertExists($document->path);
        $this->assertStringStartsWith('driver-documents/'.$profile->id.'/', $document->path);
        $this->assertNotSame('license.pdf', basename($document->path));
        $this->assertSame('license.pdf', $document->original_name);
        $this->assertSame(DocumentType::DrivingLicense, $document->type);
        $this->assertSame(ReviewStatus::Pending, $document->status);
        $this->assertFalse(Storage::disk('public')->exists($document->path));
    }

    public function test_document_upload_validates_type_and_size(): void
    {
        Storage::fake('local');

        $driver = $this->pendingDriver();

        Livewire::actingAs($driver)
            ->test(Profile::class)
            ->set('document_type', DocumentType::NationalCard->value)
            ->set('document', UploadedFile::fake()->create('tool.exe', 10, 'application/x-msdownload'))
            ->call('uploadDocument')
            ->assertHasErrors(['document' => 'mimes']);

        Livewire::actingAs($driver)
            ->test(Profile::class)
            ->set('document_type', DocumentType::NationalCard->value)
            ->set('document', UploadedFile::fake()->create('large.pdf', (int) config('freight.documents.max_upload_kb') + 100, 'application/pdf'))
            ->call('uploadDocument')
            ->assertHasErrors(['document' => 'max']);

        $this->assertDatabaseCount('driver_documents', 0);
    }

    public function test_document_upload_requires_a_profile(): void
    {
        Storage::fake('local');

        $component = Livewire::actingAs(User::factory()->driver()->create())
            ->test(Profile::class)
            ->set('document_type', DocumentType::NationalCard->value)
            ->set('document', UploadedFile::fake()->create('card.jpg', 50, 'image/jpeg'))
            ->call('uploadDocument');

        $this->assertNotNull($component->get('errorMessage'));
        $this->assertDatabaseCount('driver_documents', 0);
    }

    public function test_document_download_is_authorized(): void
    {
        Storage::fake('local');

        $driver = $this->pendingDriver();
        $profile = $driver->driverProfile;
        $path = 'driver-documents/'.$profile->id.'/generated-name.pdf';
        Storage::disk('local')->put($path, 'document-content');

        $document = DriverDocument::create([
            'driver_profile_id' => $profile->id,
            'type' => DocumentType::DrivingLicense,
            'path' => $path,
            'original_name' => 'license.pdf',
            'mime' => 'application/pdf',
            'size' => 16,
        ]);

        $this->get(route('documents.show', $document))->assertRedirect(route('login'));

        $this->actingAs($driver)->get(route('documents.show', $document))->assertOk();
        $this->actingAs($this->approvedDriver())->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs(User::factory()->shipper()->create())->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs(User::factory()->contentEditor()->create())->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs(User::factory()->operator()->create())->get(route('documents.show', $document))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('documents.show', $document))->assertOk();
    }

    public function test_driver_advances_status_of_own_assigned_order(): void
    {
        $driver = $this->approvedDriver();
        $shipper = User::factory()->shipper()->create(['mobile' => '09008889900']);
        $order = Order::factory()->assignedTo($driver)->create(['shipper_id' => $shipper->id]);

        $component = Livewire::actingAs($driver)
            ->test(MyJobs::class)
            ->assertSee($order->reference)
            ->assertSee('09008889900')
            ->call('advance', $order->id, 'loaded')
            ->assertSet('errorMessage', null);

        $this->assertSame(OrderStatus::Loaded, $order->fresh()->status);

        $component->call('advance', $order->id, 'in_transit');
        $this->assertSame(OrderStatus::InTransit, $order->fresh()->status);

        $component->call('advance', $order->id, 'delivered');
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
        $this->assertSame(3, $order->statusHistories()->count());
    }

    public function test_invalid_transition_shows_a_persian_error(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->assignedTo($driver)->create();

        $component = Livewire::actingAs($driver)
            ->test(MyJobs::class)
            ->call('advance', $order->id, 'delivered');

        $this->assertStringContainsString('مجاز نیست', (string) $component->get('errorMessage'));
        $this->assertSame(OrderStatus::Assigned, $order->fresh()->status);
    }

    public function test_driver_cannot_update_orders_assigned_to_someone_else(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->assignedTo($this->approvedDriver())->create();

        Livewire::actingAs($driver)
            ->test(MyJobs::class)
            ->assertDontSee($order->reference)
            ->call('advance', $order->id, 'loaded')
            ->assertForbidden();

        $this->assertSame(OrderStatus::Assigned, $order->fresh()->status);
    }
}
