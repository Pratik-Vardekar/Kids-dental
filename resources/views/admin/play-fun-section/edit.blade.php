@extends('layouts.admin')

@section('content')
@php
    $stats = $section->stats ?: [
        ['value' => '', 'label' => ''],
        ['value' => '', 'label' => ''],
        ['value' => '', 'label' => ''],
        ['value' => '', 'label' => ''],
    ];
    $stats = array_pad($stats, 4, ['value' => '', 'label' => '']);
@endphp

<div class="container-fluid">

    <div class="mb-4">
        <h4 class="mb-1">Play Fun Section</h4>
        <p class="text-muted mb-0">Edit the homepage Play. Learn. Grow hero and statistics bar.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.play-fun-section.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Hero Content</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tagline</label>
                        <input type="text" name="tagline" class="form-control"
                            value="{{ old('tagline', $section->tagline) }}"
                            placeholder="e.g. Play. Learn. Grow.">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Button Text</label>
                        <input type="text" name="button_text" class="form-control"
                            value="{{ old('button_text', $section->button_text) }}"
                            placeholder="e.g. Explore Products">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Headline</label>
                        <textarea name="headline" rows="3" class="form-control"
                            placeholder="Main headline">{{ old('headline', $section->headline) }}</textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Button URL</label>
                        <input type="text" name="button_url" class="form-control"
                            value="{{ old('button_url', $section->button_url) }}"
                            placeholder="/products or #">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hero Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        @if($section->image)
                            <div class="mt-2">
                                <img src="{{ asset($section->image) }}" alt="Hero" style="max-height:120px;" class="rounded border">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Statistics Bar</h5></div>
            <div class="card-body">
                <div class="row">
                    @foreach($stats as $i => $stat)
                        <div class="col-md-6 mb-3">
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label small">Value {{ $i + 1 }}</label>
                                    <input type="text" name="stats[{{ $i }}][value]" class="form-control"
                                        value="{{ old("stats.$i.value", $stat['value'] ?? '') }}"
                                        placeholder="e.g. 5K+">
                                </div>
                                <div class="col-8">
                                    <label class="form-label small">Label {{ $i + 1 }}</label>
                                    <input type="text" name="stats[{{ $i }}][label]" class="form-control"
                                        value="{{ old("stats.$i.label", $stat['label'] ?? '') }}"
                                        placeholder="e.g. Happy Little Customers">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Update Play Fun Section</button>
    </form>

</div>
@endsection
