@extends('layout')
@section('title')
Accueil
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">

        {{-- Bannière de bienvenue --}}
        <div class="welcome-banner mb-4">
            <h2>Bonjour, {{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</h2>
            <p>Bienvenue sur EduLMD — {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}. Voici un aperçu de votre activité.</p>
        </div>

        {{-- Indicateurs clés --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6 col-12">
                <a href="{{ route('etudiants.index') }}" class="kpi-card text-decoration-none">
                    <span class="kpi-icon indigo"><i class="bi bi-people"></i></span>
                    <span>
                        <span class="kpi-value d-block">{{ \App\Models\Etudiant::count() }}</span>
                        <span class="kpi-label">Étudiants</span>
                    </span>
                    <i class="bi bi-arrow-right kpi-arrow"></i>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 col-12">
                <a href="{{ route('inscriptions.index') }}" class="kpi-card text-decoration-none">
                    <span class="kpi-icon emerald"><i class="bi bi-clipboard2-check"></i></span>
                    <span>
                        <span class="kpi-value d-block">{{ \App\Models\Inscription::count() }}</span>
                        <span class="kpi-label">Inscriptions</span>
                    </span>
                    <i class="bi bi-arrow-right kpi-arrow"></i>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 col-12">
                <a href="{{ route('enseignants.index') }}" class="kpi-card text-decoration-none">
                    <span class="kpi-icon amber"><i class="bi bi-person-badge"></i></span>
                    <span>
                        <span class="kpi-value d-block">{{ \App\Models\Enseignant::count() }}</span>
                        <span class="kpi-label">Enseignants</span>
                    </span>
                    <i class="bi bi-arrow-right kpi-arrow"></i>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 col-12">
                <a href="{{ route('ue.index') }}" class="kpi-card text-decoration-none">
                    <span class="kpi-icon violet"><i class="bi bi-journal-bookmark"></i></span>
                    <span>
                        <span class="kpi-value d-block">{{ \App\Models\Ue::count() }}</span>
                        <span class="kpi-label">Unités d'enseignement</span>
                    </span>
                    <i class="bi bi-arrow-right kpi-arrow"></i>
                </a>
            </div>
        </div>

        {{-- Accès rapides --}}
        <h5 class="fw-bold mb-3" style="color:var(--ed-ink);">Accès rapides</h5>
        <div class="row g-3">
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('paiements.index') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon emerald" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-cash-coin"></i></span>
                    <span class="qc-title">Paiements</span>
                    <span class="qc-desc">Enregistrer et suivre les règlements des étudiants.</span>
                </a>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('evaluations.index') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon sky" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-calendar3-event"></i></span>
                    <span class="qc-title">Évaluations</span>
                    <span class="qc-desc">Planifier les compositions et examens.</span>
                </a>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('afficherNotes') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon indigo" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-pencil-square"></i></span>
                    <span class="qc-title">Insertion des notes</span>
                    <span class="qc-desc">Saisir et importer les notes par évaluation.</span>
                </a>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('cours.index') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon amber" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-book"></i></span>
                    <span class="qc-title">Cours</span>
                    <span class="qc-desc">Gérer les cours et leur programmation.</span>
                </a>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('ecue.index') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon violet" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-journal-text"></i></span>
                    <span class="qc-title">ECUE</span>
                    <span class="qc-desc">Éléments constitutifs des unités d'enseignement.</span>
                </a>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('previsions.index') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon rose" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-graph-up-arrow"></i></span>
                    <span class="qc-title">Prévisions</span>
                    <span class="qc-desc">Échéanciers et prévisions de paiement.</span>
                </a>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('recapitulatifGrade') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon indigo" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-award"></i></span>
                    <span class="qc-title">Délibération</span>
                    <span class="qc-desc">Récapitulatifs, relevés et attestations.</span>
                </a>
            </div>
            <div class="col-xl-3 col-md-4 col-sm-6 col-12">
                <a href="{{ route('parcours') }}" class="quick-card text-decoration-none">
                    <span class="kpi-icon sky" style="width:44px;height:44px;font-size:1.15rem;"><i class="bi bi-signpost-2"></i></span>
                    <span class="qc-title">Parcours étudiant</span>
                    <span class="qc-desc">Consulter le parcours complet d'un étudiant.</span>
                </a>
            </div>
        </div>

    </div>
</section>
@endsection
