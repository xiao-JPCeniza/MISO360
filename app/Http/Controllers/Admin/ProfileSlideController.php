<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProfileSlideRequest;
use App\Http\Requests\Admin\UpdateProfileSlideRequest;
use App\Models\ProfileSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileSlideController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ProfileSlide::query();

        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('subtitle', 'like', '%'.$search.'%');
            });
        }

        $sort = $request->string('sort', 'order')->toString();
        match ($sort) {
            'latest' => $query->orderByDesc('updated_at'),
            'active' => $query->orderByRaw('is_active DESC')->orderBy('sort_order')->orderBy('id'),
            default => $query->ordered(),
        };

        $slides = $query->get()->map(fn (ProfileSlide $slide) => $this->slideToResource($slide));

        return Inertia::render('admin/posts/Index', [
            'slides' => $slides,
            'filters' => [
                'search' => $search->toString(),
                'sort' => $sort,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/posts/Create', [
            'textPositionOptions' => \App\Enums\ProfileSlideTextPosition::options(),
        ]);
    }

    public function store(StoreProfileSlideRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('image');
        $path = $file->store('profile-slides', 'public');

        $maxOrder = ProfileSlide::max('sort_order') ?? 0;

        ProfileSlide::create([
            'image_path' => $path,
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'text_position' => $validated['text_position'],
            'sort_order' => (int) ($validated['sort_order'] ?? $maxOrder + 1),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.posts.index')->with('status', 'Slide created successfully.');
    }

    public function edit(ProfileSlide $profileSlide): Response
    {
        $slide = $this->slideToResource($profileSlide);

        return Inertia::render('admin/posts/Edit', [
            'slide' => $slide,
            'textPositionOptions' => \App\Enums\ProfileSlideTextPosition::options(),
        ]);
    }

    public function update(UpdateProfileSlideRequest $request, ProfileSlide $profileSlide): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($profileSlide->image_path);
            $path = $request->file('image')->store('profile-slides', 'public');
            $profileSlide->image_path = $path;
        }

        $profileSlide->title = $validated['title'];
        $profileSlide->subtitle = $validated['subtitle'] ?? null;
        $profileSlide->text_position = $validated['text_position'];
        $profileSlide->sort_order = (int) ($validated['sort_order'] ?? $profileSlide->sort_order);
        $profileSlide->is_active = $validated['is_active'] ?? $profileSlide->is_active;
        $profileSlide->save();

        return redirect()->route('admin.posts.index')->with('status', 'Slide updated successfully.');
    }

    public function destroy(ProfileSlide $profileSlide): RedirectResponse
    {
        Storage::disk('public')->delete($profileSlide->image_path);
        $profileSlide->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Slide deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function slideToResource(ProfileSlide $slide): array
    {
        $disk = Storage::disk('public');

        return [
            'id' => $slide->id,
            'imageUrl' => $disk->exists($slide->image_path) ? '/storage/'.$slide->image_path : null,
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'textPosition' => $slide->text_position->value,
            'sortOrder' => $slide->sort_order,
            'isActive' => $slide->is_active,
            'updatedAt' => $slide->updated_at?->toDateTimeString(),
        ];
    }
}
