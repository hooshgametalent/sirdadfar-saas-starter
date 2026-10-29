<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\AuditLogger;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Site wide settings stored in the settings table through App\Support\Settings
 * (cached, the cache is cleared on every save).
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    /**
     * @var list<string>
     */
    public const KEYS = [
        'site_name',
        'support_phone',
        'support_email',
        'address',
        'hero_text',
        'commission_percent',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_ADMIN;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'تنظیمات سایت';

    protected static ?string $title = 'تنظیمات سایت';

    protected static ?string $slug = 'settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Setting::class) ?? false;
    }

    public function mount(): void
    {
        $values = [];

        foreach (self::KEYS as $key) {
            $values[$key] = Settings::get($key);
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('اطلاعات عمومی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')->label('نام سایت')->maxLength(100),
                        TextInput::make('support_phone')
                            ->label('تلفن پشتیبانی')
                            ->tel()
                            ->maxLength(30)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('support_email')
                            ->label('ایمیل پشتیبانی')
                            ->email()
                            ->maxLength(150)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('commission_percent')
                            ->label('درصد کمیسیون')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('٪'),
                        Textarea::make('address')->label('نشانی دفتر')->rows(2)->maxLength(500)->columnSpanFull(),
                    ]),
                Section::make('صفحه اصلی')
                    ->schema([
                        Textarea::make('hero_text')->label('متن معرفی بالای صفحه اصلی')->rows(3)->maxLength(500),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions()),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $changed = [];

        foreach (self::KEYS as $key) {
            $value = $data[$key] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            if ($value !== Settings::get($key)) {
                $changed[] = $key;
            }

            Settings::set($key, filled($value) ? $value : null);
        }

        if ($changed !== []) {
            app(AuditLogger::class)->log('settings.updated', null, ['keys' => $changed]);
        }

        Notification::make()->title('تنظیمات ذخیره شد.')->success()->send();
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('ذخیره تنظیمات')
                ->submit('form'),
        ];
    }
}
