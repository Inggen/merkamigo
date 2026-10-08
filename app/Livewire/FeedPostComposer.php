<?php

namespace App\Livewire;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Actions\PublishPublicEventToFeed;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Domain\Social\Actions\CreatePost;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class FeedPostComposer extends Component
{
    use WithFileUploads;

    public ?int $businessId = null;

    public string $body = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    public ?TemporaryUploadedFile $video = null;

    public ?int $eventId = null;

    public ?int $rewardId = null;

    /** @var array<int, int|string> */
    public array $productIds = [];

    public function mount(): void
    {
        $this->businessId = $this->publishingBusinesses()->first()?->id;
    }

    /** @return Collection<int, Business> */
    #[Computed]
    public function businesses(): Collection
    {
        return $this->publishingBusinesses();
    }

    #[Computed]
    public function selectedBusiness(): ?Business
    {
        return $this->businesses()->firstWhere('id', $this->businessId);
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        $business = $this->selectedBusiness();

        if (! $business) {
            return new Collection;
        }

        return $business->products()
            ->where('status', 'publicado')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, PublicEvent> */
    #[Computed]
    public function events(): Collection
    {
        $business = $this->selectedBusiness();

        if (! $business) {
            return new Collection;
        }

        return $business->publicEvents()
            ->where('status', PublicEvent::PUBLICADO)
            ->where('starts_at', '>=', now())
            ->whereDoesntHave('post')
            ->orderBy('starts_at')
            ->get();
    }

    /** @return Collection<int, LoyaltyReward> */
    #[Computed]
    public function rewards(): Collection
    {
        $business = $this->selectedBusiness();

        if (! $business) {
            return new Collection;
        }

        return $business->loyaltyRewards()
            ->where('status', LoyaltyReward::PUBLICADO)
            ->orderBy('title')
            ->get()
            ->filter(fn (LoyaltyReward $reward): bool => $reward->isRedeemable())
            ->values();
    }

    public function updatedBusinessId(): void
    {
        $this->reset(['photos', 'video', 'eventId', 'rewardId', 'productIds']);
        unset($this->selectedBusiness, $this->products, $this->events, $this->rewards);
    }

    public function appendEmoji(string $emoji): void
    {
        $allowed = ['😊', '🎉', '❤️', '🔥', '👏', '✨', '🚀', '🙌', '📍', '🛍️', '🎁', '📅'];

        if (in_array($emoji, $allowed, true)) {
            $this->body = rtrim($this->body).' '.$emoji;
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function removeVideo(): void
    {
        $this->reset('video');
    }

    public function selectEvent(int $eventId): void
    {
        abort_unless($this->events()->contains('id', $eventId), 403);

        $this->eventId = $eventId;
        $this->reset(['rewardId', 'photos', 'video', 'productIds']);
    }

    public function removeEvent(): void
    {
        $this->reset('eventId');
    }

    public function selectReward(int $rewardId): void
    {
        abort_unless($this->rewards()->contains('id', $rewardId), 403);

        $this->rewardId = $rewardId;
        $this->reset('eventId');
    }

    public function removeReward(): void
    {
        $this->reset('rewardId');
    }

    public function publish(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $business = $this->publishingBusinesses()->firstWhere('id', $this->businessId);

        abort_unless($business instanceof Business, 403);

        $this->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'photos' => ['array', 'max:'.config('media.post_photo.max_files', 10)],
            'photos.*' => [
                'file',
                'mimes:'.implode(',', config('media.post_photo.mimes', ['jpg', 'jpeg', 'png', 'webp'])),
                'max:'.config('media.post_photo.max_kb', 5120),
            ],
            'video' => [
                'nullable',
                'file',
                'mimes:'.implode(',', config('media.post_video.mimes', ['mp4', 'webm', 'mov'])),
                'max:'.config('media.post_video.max_kb', 51200),
            ],
            'eventId' => ['nullable', 'integer'],
            'rewardId' => ['nullable', 'integer'],
            'productIds' => ['array'],
            'productIds.*' => ['integer', 'distinct'],
        ]);

        if ($this->video && $this->photos !== []) {
            $this->addError('video', __('Publica un video o una galería de imágenes, no ambos a la vez.'));

            return;
        }

        if ($this->eventId) {
            $event = $this->events()->firstWhere('id', $this->eventId);

            abort_unless($event instanceof PublicEvent, 403);

            try {
                app(PublishPublicEventToFeed::class)->handle($event, $user);
            } catch (EventActionException $exception) {
                $this->addError('eventId', $exception->getMessage());

                return;
            }

            $this->published();

            return;
        }

        $type = match (true) {
            $this->video instanceof TemporaryUploadedFile => 'video',
            count($this->photos) === 0 => 'texto',
            count($this->photos) === 1 => 'imagen',
            default => 'carrusel',
        };

        $media = $this->video ? [$this->video] : $this->photos;

        try {
            app(CreatePost::class)->handle($business, [
                'type' => $type,
                'body' => $this->body,
                'product_ids' => $this->productIds,
                'loyalty_reward_id' => $this->rewardId,
            ], $media, $user);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->published();
    }

    public function render(): View
    {
        return view('livewire.feed-post-composer');
    }

    /** @return Collection<int, Business> */
    private function publishingBusinesses(): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new Collection;
        }

        return $user->businesses()
            ->wherePivot('status', 'activo')
            ->where('businesses.status', 'publicado')
            ->with(['organization', 'storefront'])
            ->orderBy('businesses.name')
            ->get()
            ->filter(fn (Business $business): bool => $this->canManage($user, $business))
            ->values();
    }

    private function canManage(User $user, Business $business): bool
    {
        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($business->id);
            $user->unsetRelation('roles');

            return $user->can('update', $business);
        } finally {
            setPermissionsTeamId($previousTeamId);
            $user->unsetRelation('roles');
        }
    }

    private function published(): void
    {
        $this->reset(['body', 'photos', 'video', 'eventId', 'rewardId', 'productIds']);
        Flux::toast(variant: 'success', text: __('Tu publicación ya está en el muro.'));

        $this->redirectRoute('home', navigate: true);
    }
}
