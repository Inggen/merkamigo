<article class="welcome-feature">
    <span class="welcome-icon"><x-dynamic-component :component="'flux::icon.'.$feature['icon']" class="size-8" variant="outline" /></span>
    <div><h3>{{ __($feature['title']) }}</h3><p>{{ __($feature['description']) }}</p></div>
</article>
