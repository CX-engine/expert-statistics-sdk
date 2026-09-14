<x-expert-statistics::cluster-layout
  :title="__('expert-statistics::pbx.expert_statistics.home_title')"
  :subtitle="__('expert-statistics::pbx.expert_statistics.home_subtitle', ['count' => count($this->getFeatures())])"
>
  <dl class="space-y-6">
    @foreach ($this->getFeatures() as $feature)
      <div class="relative pl-10">
        <dt class="font-semibold text-primary-700 dark:text-primary-400">
          <x-dynamic-component
            :component="$feature['icon']"
            class="absolute left-0 top-0.5 h-5 w-5 text-primary-600 dark:text-primary-400"
          />
          <a href="{{ $feature['url'] }}" class="hover:underline">{{ $feature['name'] }}.</a>
        </dt>
        {{ ' ' }}
        <dd class="inline text-sm text-gray-600 dark:text-gray-400">{{ $feature['description'] }}</dd>
      </div>
    @endforeach
  </dl>
</x-expert-statistics::cluster-layout>
