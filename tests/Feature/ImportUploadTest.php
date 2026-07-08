<?php

use App\Enums\Upload\UploadStatusEnum;
use App\Jobs\ProcessCsvUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guests are redirected to login when visiting imports index page', function () {
    $response = $this->get(route('uploads.index'));

    $response->assertRedirect(route('login'));
});

test('guests are redirected to login when visiting import upload page', function () {
    $response = $this->get(route('uploads.create'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view imports index page', function () {
    $user = User::factory()->create();
    $user->uploads()->create([
        'file_path' => 'imports/test_file.csv',
        'original_file_name' => 'test_file.csv',
        'status' => UploadStatusEnum::PENDING->value,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('uploads.index'));

    $response->assertOk();
    $response->assertSee('test_file.csv');
});

test('authenticated users can view import upload page', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('uploads.create'));

    $response->assertOk();
});

test('csv upload dispatches processing job', function () {
    Storage::fake('local');
    Queue::fake();

    $this->actingAs(User::factory()->create());

    $csv = UploadedFile::fake()->createWithContent(
        'products.csv',
        "Handle,Title\nmodern-desk-lamp,Modern Desk Lamp\n",
    );

    $response = $this->post(route('uploads.store'), [
        'csv_file' => $csv,
    ]);

    $response->assertRedirect(route('uploads.index'));

    Queue::assertPushed(ProcessCsvUpload::class, function (ProcessCsvUpload $job) {
        return str_starts_with($job->upload->file_path, 'imports/');
    });
});

test('upload requires csv file', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('uploads.store'), []);

    $response->assertSessionHasErrors('csv_file');
});

test('upload rejects non csv files', function () {
    $this->actingAs(User::factory()->create());

    $image = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->post(route('uploads.store'), [
        'csv_file' => $image,
    ]);

    $response->assertSessionHasErrors('csv_file');
});
