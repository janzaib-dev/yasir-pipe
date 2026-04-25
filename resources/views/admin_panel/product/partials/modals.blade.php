{{-- Category Modal --}}
<div id="categoryModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-md);">
            <form action="{{ route('store.category') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">New Category</h6>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="page" value="product_page">
                    <div class="mb-3">
                        <label class="form-label-pro">Category Name</label>
                        <input type="text" name="name" class="form-control-pro" required placeholder="e.g. Ceramics">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Subcategory Modal --}}
<div id="subcategoryModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-md);">
            <form action="{{ route('store.subcategory') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">New Subcategory</h6>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="page" value="product_page">
                    <div class="mb-3">
                        <label class="form-label-pro">Parent Category</label>
                        <select name="category_id" class="form-select form-control-pro">
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-pro">Name</label>
                        <input type="text" name="name" class="form-control-pro" required placeholder="e.g. Floor Tiles">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Create Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>
