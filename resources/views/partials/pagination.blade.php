@php
  /*
   * Pagination maison — aucune dépendance à un thème Bootstrap.
   *
   * @param  \Illuminate\Pagination\LengthAwarePaginator $paginateur
   *
   * La fenêtre glissante évite d'afficher 40 numéros de page quand la base
   * compte des milliers de lignes.
   */
  $courante = $paginateur->currentPage();
  $derniere = $paginateur->lastPage();
  $fenetre = 2;

  $debut = max(1, $courante - $fenetre);
  $fin = min($derniere, $courante + $fenetre);

  $pages = range($debut, $fin);

  /* Ellipse de début : on garde toujours accès à la page 1. */
  if ($debut > 1) {
      array_unshift($pages, 1);
      if ($debut > 2) {
          array_splice($pages, 1, 0, ['…']);
      }
  }

  /* Ellipse de fin : on garde toujours accès à la dernière page. */
  if ($fin < $derniere) {
      if ($fin < $derniere - 1) {
          $pages[] = '…';
      }
      $pages[] = $derniere;
  }
@endphp

@if ($derniere > 1)
  <nav class="ed-pager" aria-label="Pagination">
    <p class="ed-pager-info">
      {{ number_format($paginateur->firstItem() ?: 0, 0, ',', ' ') }}–{{ number_format($paginateur->lastItem() ?: 0, 0, ',', ' ') }}
      sur {{ number_format($paginateur->total(), 0, ',', ' ') }}
    </p>

    <div class="ed-pager-pages">
      @if ($courante > 1)
        <a class="ed-pager-btn" href="{{ $paginateur->previousPageUrl() }}" rel="prev" aria-label="Page précédente">
          <i class="ri-arrow-left-s-line"></i>
        </a>
      @else
        <span class="ed-pager-btn off" aria-hidden="true"><i class="ri-arrow-left-s-line"></i></span>
      @endif

      @foreach ($pages as $p)
        @if ($p === '…')
          <span class="ed-pager-ellipse">…</span>
        @elseif ($p === $courante)
          <span class="ed-pager-btn on" aria-current="page">{{ $p }}</span>
        @else
          <a class="ed-pager-btn" href="{{ $paginateur->url($p) }}">{{ $p }}</a>
        @endif
      @endforeach

      @if ($courante < $derniere)
        <a class="ed-pager-btn" href="{{ $paginateur->nextPageUrl() }}" rel="next" aria-label="Page suivante">
          <i class="ri-arrow-right-s-line"></i>
        </a>
      @else
        <span class="ed-pager-btn off" aria-hidden="true"><i class="ri-arrow-right-s-line"></i></span>
      @endif
    </div>
  </nav>
@endif
