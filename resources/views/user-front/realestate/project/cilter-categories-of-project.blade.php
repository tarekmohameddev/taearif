<div class="filter-by-category">
    <form method="GET" action="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
        <div class="form-group">
            <label for="category">{{ $keywords['Filter by Category'] ?? __('Filter by Category') }}</label>
            <select name="category" id="category" class="form-control">
                <option value="">{{ $keywords['All Categories'] ?? __('All Categories') }}</option>
                @foreach ($allCategories as $category)
                    <option value="{{ $category->id }}" {{ $selectedCategoryId == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

        </div>
        <button type="submit" class="btn btn-primary mt-2">{{ $keywords['Filter'] ?? __('Filter') }}</button>
    </form>
</div>
