@extends('layout')
@section('title')
Accueil
@endsection
@section('content')
<section class="px-6 pb-2 pt-5">

    {{-- Bannière de bienvenue --}}
    <div class="welcome-banner mb-6">
        <h2>Bonjour, {{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</h2>
        <p>Bienvenue sur EduLMD — {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}. Voici un aperçu de votre activité.</p>
    </div>

    {{-- Indicateurs clés --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-7">
        <a href="{{ route('etudiants.index') }}" class="kpi-card text-decoration-none">
            <div class="kpi-top">
                <span class="kpi-icon bordeaux"><i class="ri-team-line"></i></span>
                <i class="ri-arrow-right-line kpi-arrow"></i>
            </div>
            <div class="kpi-value">{{ number_format(\App\Models\Etudiant::count(), 0, ',', ' ') }}</div>
            <div class="kpi-label">Étudiants</div>
        </a>
        <a href="{{ route('inscriptions.index') }}" class="kpi-card text-decoration-none">
            <div class="kpi-top">
                <span class="kpi-icon bleu"><i class="ri-file-list-3-line"></i></span>
                <i class="ri-arrow-right-line kpi-arrow"></i>
            </div>
            <div class="kpi-value">{{ number_format(\App\Models\Inscription::count(), 0, ',', ' ') }}</div>
            <div class="kpi-label">Inscriptions</div>
        </a>
        <a href="{{ route('enseignants.index') }}" class="kpi-card text-decoration-none">
            <div class="kpi-top">
                <span class="kpi-icon sombre"><i class="ri-id-card-line"></i></span>
                <i class="ri-arrow-right-line kpi-arrow"></i>
            </div>
            <div class="kpi-value">{{ number_format(\App\Models\Enseignant::count(), 0, ',', ' ') }}</div>
            <div class="kpi-label">Enseignants</div>
        </a>
        <a href="{{ route('ue.index') }}" class="kpi-card text-decoration-none">
            <div class="kpi-top">
                <span class="kpi-icon vert"><i class="ri-book-read-line"></i></span>
                <i class="ri-arrow-right-line kpi-arrow"></i>
            </div>
            <div class="kpi-value">{{ number_format(\App\Models\Ue::count(), 0, ',', ' ') }}</div>
            <div class="kpi-label">Unités d'enseignement</div>
        </a>
    </div>

    {{-- Accès rapides --}}
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-[1rem] font-bold text-encre tracking-tight">Accès rapides</h2>
        <span class="text-[0.75rem] text-encre-faint">Raccourcis vers les opérations courantes</span>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        <a href="{{ route('paiements.index') }}" class="quick-card text-decoration-none">
            <span class="qc-icon bordeaux"><i class="ri-hand-coin-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">Paiements</span>
                <span class="qc-desc block">Enregistrer et suivre les règlements.</span>
            </span>
        </a>

        <a href="{{ route('evaluations.index') }}" class="quick-card text-decoration-none">
            <span class="qc-icon bleu"><i class="ri-calendar-event-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">Évaluations</span>
                <span class="qc-desc block">Planifier les compositions et examens.</span>
            </span>
        </a>

        <a href="{{ route('afficherNotes') }}" class="quick-card text-decoration-none">
            <span class="qc-icon sombre"><i class="ri-edit-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">Insertion des notes</span>
                <span class="qc-desc block">Saisir et importer les notes.</span>
            </span>
        </a>

        <a href="{{ route('cours.index') }}" class="quick-card text-decoration-none">
            <span class="qc-icon bordeaux"><i class="ri-book-2-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">Cours</span>
                <span class="qc-desc block">Gérer les cours et leur programmation.</span>
            </span>
        </a>

        <a href="{{ route('ecue.index') }}" class="quick-card text-decoration-none">
            <span class="qc-icon bleu"><i class="ri-file-text-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">ECUE</span>
                <span class="qc-desc block">Éléments constitutifs des UE.</span>
            </span>
        </a>

        <a href="{{ route('previsions.index') }}" class="quick-card text-decoration-none">
            <span class="qc-icon sombre"><i class="ri-line-chart-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">Prévisions</span>
                <span class="qc-desc block">Échéanciers de paiement.</span>
            </span>
        </a>

        <a href="{{ route('recapitulatifGrade') }}" class="quick-card text-decoration-none">
            <span class="qc-icon bordeaux"><i class="ri-award-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">Délibération</span>
                <span class="qc-desc block">Récapitulatifs et relevés.</span>
            </span>
        </a>

        <a href="{{ route('parcours') }}" class="quick-card text-decoration-none">
            <span class="qc-icon bleu"><i class="ri-signpost-line"></i></span>
            <span class="min-w-0">
                <span class="qc-title block">Parcours étudiant</span>
                <span class="qc-desc block">Consulter le parcours complet.</span>
            </span>
        </a>

    </div>

</section>
@endsection
