@props(['picture', 'title' => 'You unlocked 2 new Achievements', 'class' => ''])

<article {{ $attributes->class(['widget-card', 'widget-illustration', $class]) }} data-widget-card>
    {{ $slot }}
    <img src="{{ $picture }}" alt="" loading="lazy">
    <h2>{{ $title }}</h2>
    <p>Congrats, your efforts have been rewarded. Keep up like this!</p>
</article>
