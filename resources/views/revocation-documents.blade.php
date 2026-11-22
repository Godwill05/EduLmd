@extends('layout')

@section('title')
Révocation de documents
@endsection

@section('css')
<style>
  .rv-card { background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem;
             box-shadow:var(--ed-ombre); overflow:hidden; }
  .rv-filtres { padding:1rem 1.1rem; border-bottom:1px solid var(--ed-ligne); background:#FCFCFD; }
  .rv-lbl { display:block; font-size:.68rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.04em; color:#8A8D94; margin-bottom:.25rem; }

  .rv-item { display:flex; gap:1rem; padding:1rem 1.1rem; border-bottom:1px solid #F2F2F5;
             align-items:flex-start; }
  .rv-item:last-child { border-bottom:0; }
  .rv-item:hover { background:#FDFAFA; }

  .rv-titre { font-size:.86rem; font-weight:700; color:#0B0B0B; }
  .rv-meta { font-size:.73rem; color:#8A8D94; margin-top:.2rem;
             display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
  .rv-meta .sep { color:#D9D9DE; }
  .rv-code { font-family:ui-monospace,monospace; font-size:.72rem; background:#F4F4F6;
             padding:.08rem .4rem; border-radius:.3rem; letter-spacing:.03em; }

  .rv-badge { font-size:.66rem; font-weight:800; text-transform:uppercase;
              letter-spacing:.05em; padding:.2rem .5rem; border-radius:99px; }
  .rv-badge.valide  { background:#EAF7EE; color:#15803D; }
  .rv-badge.revoque { background:#FDECEC; color:#B91C1C; }

  .rv-motif { margin-top:.5rem; font-size:.75rem; background:#FDF2F2;
              border-left:3px solid #B91C1C; padding:.45rem .7rem; border-radius:.35rem; }
  .rv-motif b { color:#B91C1C; }

  .rv-actions { flex:0 0 auto; display:flex; gap:.4rem; align-items:flex-start; }

  .rv-vide { text-align:center; padding:3rem 1.5rem; color:#8A8D94; }
  .rv-vide i { font-size:2.2rem; }

  .rv-chips { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem; }
  .rv-chip { display:inline-flex; align-items:center; gap:.4rem; background:#fff;
             border:1px solid var(--ed-ligne); border-radius:99px; padding:.35rem .8rem;
             font-size:.78rem; font-weight:600; color:#5C5F66; text-decoration:none; }
  .rv-chip:hover { border-color:#C9A3AC; color:#822A40; }
  .rv-chip.on { background:#822A40; border-color:#822A40; color:#fff; }
</style>
@endsection

@section('content')
<div class="pagetitle">
  <h1>Révocation de documents</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
      <li class="breadcrumb-item active">Révocation</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row">
    <div class="col-lg-12">

      <div class="alert alert-warning d-flex gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
          <strong>Une révocation est immédiate et publique.</strong>
          Le QR code imprimé sur le papier ne peut pas être retiré : toute personne qui le
          scanne verra « document révoqué » et le motif. Ne révoquez qu'en cas de fraude
          avérée ou d'erreur de délivrance, et vérifiez l'identité avant d'agir.
        </div>
      </div>

      <div class="rv-chips">
        <a href="{{ route('revocation.index', ['statut' => 'valide'] + request()->only('q')) }}"
           class="rv-chip {{ $statut === 'valide' ? 'on' : '' }}">
          Valides <b>{{ number_format($compte['valide'], 0, ',', ' ') }}</b>
        </a>
        <a href="{{ route('revocation.index', ['statut' => 'revoque'] + request()->only('q')) }}"
           class="rv-chip {{ $statut === 'revoque' ? 'on' : '' }}">
          Révoqués <b>{{ number_format($compte['revoque'], 0, ',', ' ') }}</b>
        </a>
        <a href="{{ route('revocation.index', ['statut' => 'tous'] + request()->only('q')) }}"
           class="rv-chip {{ $statut === 'tous' ? 'on' : '' }}">Tous</a>
      </div>

      <div class="rv-card">
        <form method="GET" action="{{ route('revocation.index') }}" class="rv-filtres">
          <input type="hidden" name="statut" value="{{ $statut }}">
          <div class="row g-2 align-items-end">
            <div class="col-md-6">
              <label class="rv-lbl" for="q">Rechercher</label>
              <input type="search" name="q" id="q" value="{{ $q }}" class="form-control form-control-sm"
                     placeholder="Nom, matricule, code du document ou référence">
            </div>
            <div class="col-md-3">
              <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Rechercher</button>
              <a href="{{ route('revocation.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
            </div>
          </div>
        </form>

        @if (session('succes'))
          <div class="alert alert-success m-3 mb-0">{{ session('succes') }}</div>
        @endif
        @if (session('erreur'))
          <div class="alert alert-danger m-3 mb-0">{{ session('erreur') }}</div>
        @endif

        @forelse ($documents as $doc)
          <div class="rv-item">
            <div class="flex-grow-1" style="min-width:0;">
              <div class="rv-titre">
                {{ $doc->nom_complet }}
                <span class="rv-badge {{ $doc->estValide() ? 'valide' : 'revoque' }}">
                  {{ $doc->estValide() ? 'valide' : 'révoqué' }}
                </span>
              </div>
              <div class="rv-meta">
                <span><i class="bi bi-award"></i> {{ $doc->libelleType() }}</span>
                <span class="sep">|</span>
                <span><i class="bi bi-hash"></i> {{ $doc->matricule }}</span>
                <span class="sep">|</span>
                <span class="rv-code">{{ $doc->code }}</span>
                @if ($doc->reference)
                  <span class="sep">|</span>
                  <span>{{ $doc->reference }}</span>
                @endif
              </div>
              <div class="rv-meta">
                <span><i class="bi bi-calendar-event"></i> Délivré le {{ $doc->date_delivrance ?: '—' }}</span>
                @if ($doc->cycle)
                  <span class="sep">|</span><span>{{ $doc->cycle }}</span>
                @endif
                @if ($doc->mention)
                  <span class="sep">|</span><span>{{ $doc->mention }}</span>
                @endif
                <span class="sep">|</span>
                <span><i class="bi bi-printer"></i> {{ $doc->nb_impressions }} impression(s)</span>
                @if ($doc->emis_par)
                  <span class="sep">|</span><span>par {{ $doc->emis_par }}</span>
                @endif
              </div>

              @unless ($doc->estValide())
                <div class="rv-motif">
                  <b>Révoqué le {{ optional($doc->revoque_le)->format('d/m/Y à H:i') }}</b><br>
                  {{ $doc->motif_revocation }}
                </div>
              @endunless
            </div>

            <div class="rv-actions">
              @if ($doc->estValide())
                {{-- Le motif est obligatoire : le formulaire s'ouvre dans une
                     boîte de dialogue pour forcer la saisie avant l'envoi. --}}
                <button type="button" class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#revoquer{{ $doc->id }}">
                  <i class="bi bi-slash-circle me-1"></i>Révoquer
                </button>

                <div class="modal fade" id="revoquer{{ $doc->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <form method="POST" action="{{ route('revocation.revoquer', $doc->id) }}" class="modal-content">
                      @csrf
                      <div class="modal-header">
                        <h5 class="modal-title">Révoquer le document de {{ $doc->nom_complet }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                      </div>
                      <div class="modal-body">
                        <p class="mb-2" style="font-size:.85rem;">
                          Le QR code <span class="rv-code">{{ $doc->code }}</span> continuera de répondre,
                          mais indiquera « révoqué » avec le motif ci-dessous.
                        </p>
                        <label for="motif{{ $doc->id }}" class="form-label">
                          Motif <span class="text-danger">*</span>
                        </label>
                        <textarea name="motif" id="motif{{ $doc->id }}" rows="3" class="form-control" required
                                  maxlength="255" placeholder="Ex. : diplôme délivré sur la base d'un relevé falsifié."></textarea>
                        <div class="form-text">Au moins 10 caractères. Ce texte sera lisible publiquement.</div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Confirmer la révocation</button>
                      </div>
                    </form>
                  </div>
                </div>
              @else
                <form method="POST" action="{{ route('revocation.retablir', $doc->id) }}"
                      onsubmit="return confirm('Rétablir ce document ? Il redeviendra valide aux yeux de tous.');">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Rétablir
                  </button>
                </form>
              @endif
            </div>
          </div>
        @empty
          <div class="rv-vide">
            <i class="bi bi-shield-check"></i>
            <p class="mt-2 mb-0">
              @if ($q !== '')
                Aucun document ne correspond à « {{ $q }} ».
              @elseif ($statut === 'revoque')
                Aucun document n'a été révoqué. C'est une bonne nouvelle.
              @else
                Aucun document émis pour l'instant.
              @endif
            </p>
          </div>
        @endforelse
      </div>

      <div class="mt-4">@include('partials.pagination', ['paginateur' => $documents])</div>

    </div>
  </div>
</section>
@endsection
