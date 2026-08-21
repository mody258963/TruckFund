<?php

namespace App\Livewire\Admin;

use App\Enums\AutoProductType;
use App\Models\AutoProduct;
use App\Models\FinancialProduct;
use App\Models\Merchant;
use App\Models\Supplier;
use App\Services\CatalogService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class CatalogManager extends Component
{
    use WithPagination;

    public string $modelClass;

    public string $titleKey;

    public string $routeName;

    /** @var list<string> */
    public array $fields = [];

    /** @var list<string> */
    public array $numericFields = [];

    /** @var list<string> */
    public array $decimalFields = [];

    public bool $hasIsActive = true;

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public string $search = '';

    public function mount(string $type): void
    {
        abort_unless(auth()->user()?->role->canAccessCatalog(), 403);

        [$this->modelClass, $this->titleKey, $this->routeName, $this->fields] = match ($type) {
            'merchants' => [Merchant::class, 'nav.merchants', 'merchants', ['name', 'type', 'contact_email']],
            'financial-products' => [FinancialProduct::class, 'nav.financial_products', 'financial-products', ['name', 'product_code', 'percentage', 'description']],
            'auto-products' => [AutoProduct::class, 'nav.auto_products', 'auto-products', ['brand', 'name', 'model', 'type', 'model_year', 'chassis', 'price']],
            'suppliers' => [Supplier::class, 'nav.suppliers', 'suppliers', ['name', 'phone', 'address', 'governorate', 'truck_type', 'city']],
            default => throw new \InvalidArgumentException('Unknown catalog type'),
        };

        $this->numericFields = match ($this->routeName) {
            'merchants' => ['type'],
            'auto-products' => ['type', 'model_year'],
            'suppliers' => ['truck_type'],
            default => [],
        };

        $this->decimalFields = match ($this->routeName) {
            'financial-products' => ['percentage'],
            'auto-products' => ['price'],
            default => [],
        };

        $this->hasIsActive = in_array($this->routeName, ['merchants', 'financial-products', 'auto-products', 'suppliers'], true);

        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->form = [];
        foreach ($this->fields as $field) {
            $this->form[$field] = in_array($field, $this->numericFields, true) || in_array($field, $this->decimalFields, true)
                ? 0
                : '';
        }
        if ($this->hasIsActive) {
            $this->form['is_active'] = true;
        }
        if (in_array('type', $this->fields, true)) {
            $this->form['type'] = 1;
        }
        if (in_array('truck_type', $this->fields, true)) {
            $this->form['truck_type'] = 1;
        }
    }

    public function create(): void
    {
        $this->editingId = null;
        $this->resetForm();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $model = $this->modelClass::query()->findOrFail($id);
        $this->editingId = $id;
        $this->resetForm();
        foreach ($this->fields as $field) {
            $value = $model->{$field};
            $this->form[$field] = $value instanceof \BackedEnum ? $value->value : $value;
        }
        if ($this->hasIsActive) {
            $this->form['is_active'] = (bool) $model->is_active;
        }
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(CatalogService $catalog): void
    {
        $this->validate($this->rules());

        $catalog->save($this->modelClass, $this->editingId, $this->preparedFormData());

        $this->showForm = false;
        $this->editingId = null;
        $this->resetForm();
    }

    /** @return array<string, mixed> */
    protected function preparedFormData(): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            $value = $this->form[$field] ?? null;
            if (in_array($field, $this->numericFields, true)) {
                $data[$field] = (int) (is_numeric($value) ? $value : 0);
            } elseif (in_array($field, $this->decimalFields, true)) {
                $data[$field] = (float) (is_numeric($value) ? $value : 0);
            } else {
                $data[$field] = is_string($value) ? trim($value) : $value;
            }
        }
        if ($this->hasIsActive) {
            $data['is_active'] = (bool) ($this->form['is_active'] ?? true);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        $rules = [];
        foreach ($this->fields as $field) {
            $key = 'form.'.$field;
            if ($this->routeName === 'auto-products' && $field === 'type') {
                $rules[$key] = ['required', Rule::enum(AutoProductType::class)];

                continue;
            }
            if ($this->routeName === 'auto-products' && $field === 'model_year') {
                $rules[$key] = ['required', 'integer', 'min:1990', 'max:'.(now()->year + 1)];

                continue;
            }
            if (in_array($field, $this->numericFields, true)) {
                $rules[$key] = ['required', 'integer', 'min:0'];

                continue;
            }
            if (in_array($field, $this->decimalFields, true)) {
                $rules[$key] = ['required', 'numeric', 'min:0'];

                continue;
            }
            if ($field === 'description' || $field === 'contact_email') {
                $rules[$key] = ['nullable', 'string', 'max:5000'];

                continue;
            }
            if ($field === 'product_code') {
                $rules[$key] = [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('financial_products', 'product_code')->ignore($this->editingId, 'product_id'),
                ];

                continue;
            }
            $rules[$key] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    public function render(CatalogService $catalog)
    {
        return view('livewire.admin.catalog-manager', [
            'items' => $catalog->paginate($this->modelClass, 15, ['search' => $this->search]),
            'autoProductTypes' => AutoProductType::cases(),
        ]);
    }
}
