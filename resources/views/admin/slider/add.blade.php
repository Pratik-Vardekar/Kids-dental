@extends('layouts.admin')

@section('content')

<div class="container-fluid my-2">

    <div class="mb-4">

        <h4 class="mb-1">Add Slider</h4>
        <p class="text-muted mb-0">Create new slider</p>

    </div>

    <div class="card">

        <div class="card-body">

            <form action="{{ route('slider.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="row g-3">

                @csrf

                <div class="col-md-6">

                    <label class="form-label">Title</label>

                    <input type="text"
                        name="title"
                        class="form-control"
                        required>

                </div>

                <div class="col-md-6">

                    <label class="form-label">Status</label>

                    <select name="status"
                        class="form-select">

                        <option value="1">Active</option>
                        <option value="0">Inactive</option>

                    </select>

                </div>

                <div class="col-12">

                    <label class="form-label">Description</label>

                    <textarea name="description"
                        class="form-control"
                        rows="4"></textarea>

                </div>

                <div class="col-md-6">

                    <label class="form-label">Image</label>

                    <input type="file"
                        name="image"
                        id="image"
                        class="form-control"
                        accept="image/*"
                        required>

                </div>

                <div class="col-md-6">

                    <label class="form-label d-none"
                        id="preview-label">

                        Preview

                    </label>

                    <img id="preview-image"
                        class="rounded border d-none"
                        style="width:120px;height:120px;object-fit:cover;">

                </div>

                <div class="col-12">

                    <button type="submit"
                        class="btn btn-primary">

                        <i class="ti ti-device-floppy me-1"></i>
                        Save Slider

                    </button>

                    <a href="{{ route('slider.index') }}"
                        class="btn btn-light">

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.getElementById('image').addEventListener('change', function(e) {

    let file = e.target.files[0];

    if(file) {

        let reader = new FileReader();

        reader.onload = function(event) {

            let preview = document.getElementById('preview-image');
            let label = document.getElementById('preview-label');

            preview.src = event.target.result;

            preview.classList.remove('d-none');
            label.classList.remove('d-none');

        }

        reader.readAsDataURL(file);

    }

});

</script>

@endsection