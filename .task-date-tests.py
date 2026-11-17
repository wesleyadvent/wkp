from pathlib import Path
p=Path('tests/Feature/InventoryTransactionTest.php');s=p.read_text();pos=s.index('    public function test_photo_');s=s[:pos]+'''    public function test_employee_verification_controls_initial_form_lock(): void
    {
        foreach (['sales', 'purchases', 'service-notes'] as $route) {
            $response = $this->get(route($route.'.create'))->assertOk();
            $response->assertSee('id="note-fields" disabled', false)->assertSee('employees/verify', false);
            $response->assertDontSee('name="tanggal"', false)->assertDontSee('name="tanggal_masuk"', false);
        }
        $this->postJson(route('employees.verify'), ['employee_name' => 'Orang luar'])->assertUnprocessable()->assertJsonValidationErrors('employee_name');
        $this->postJson(route('employees.verify'), ['employee_name' => '  karyawan UJI  '])->assertOk()->assertExactJson(['valid' => true]);
        $this->employee->update(['is_active' => false]);
        $this->postJson(route('employees.verify'), ['employee_name' => $this->employee->nama])->assertUnprocessable();
    }

    public function test_all_notes_use_current_jakarta_date_instead_of_submitted_date(): void
    {
        $this->travelTo(\\Carbon\\Carbon::parse('2026-09-11 01:30:00', 'Asia/Jakarta'));
        $product = $this->product();
        $base = ['employee_name' => $this->employee->nama, 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => '2001-01-01', 'tanggal_masuk' => '2001-01-01'];
        $this->post(route('sales.store'), $base + ['product_id' => $product->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('purchases.store'), $base + ['sumber_barang' => 'barang_luar', 'nama_barang' => 'Emas', 'berat' => 1, 'harga_per_gram_beli' => 1000000])->assertSessionHasNoErrors()->assertRedirect();
        $type = ServiceType::create(['nama_service' => 'Cuci', 'harga_default' => 10000]);
        $this->post(route('service-notes.store'), $base + ['asal_barang' => 'barang_luar', 'nama_barang_manual' => 'Emas', 'service_type_ids' => [$type->id], 'service_prices' => [10000], 'status_service' => 'diterima'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2026-09-11', Sale::firstOrFail()->tanggal->toDateString());
        $this->assertSame('2026-09-11', Purchase::firstOrFail()->tanggal->toDateString());
        $this->assertSame('2026-09-11', ServiceNote::firstOrFail()->tanggal_masuk->toDateString());
        $this->travelBack();
    }

'''+s[pos:];p.write_text(s)
