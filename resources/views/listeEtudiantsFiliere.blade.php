@extends('layout')

@section('title')
Liste des étudiants par filière
@endsection

@section('content')
@if (!empty($anneeAcademiqueId))
<script>
  window.EDULMD_BULK_CONTEXTE = {
    idAnnee: @json($anneeAcademiqueId),
    idFiliere: @json($codeFiliere ?? null)
  };
</script>
@endif
<section class="section">
    <div class="container-fluid">
        @if (session()->has('succes'))
            <div class="row">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class=" ri-checkbox-circle-line me-1"></i> {{ session('succes') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif
        @if (session()->has('echec'))
            <div class="row">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class=" ri-alert-line me-1"></i> {{ session('echec') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        <!-- Formulaire de sélection -->
        <div class="card p-3 mb-4 shadow-sm">
            <form id="formFiltre" method="GET" action="{{ route('liste-etudiants.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4 col-sm-12">
                        <label class="form-label fw-bold" for="anneeAcademique">Année académique <span class="text-danger">*</span></label>
                        <select name="anneeAcademique" id="anneeAcademique" class="form-select form-select-sm" onchange="document.getElementById('formFiltre').submit();">
                            @foreach ($anneesAcademiques as $an)
                                <option value="{{ $an->id_Annee_Acad }}" {{ (string)$anneeAcademiqueId === (string)$an->id_Annee_Acad ? 'selected' : '' }}>
                                    {{ $an->ANNEE_ACAD }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5 col-sm-12">
                        <label class="form-label fw-bold" for="anneeEtude">Filière / Année d'étude <span class="text-danger">*</span></label>
                        <select name="anneeEtude" id="anneeEtude" class="form-select form-select-sm" required>
                            <option value="">Sélectionner une filière...</option>
                            @foreach ($filieres as $f)
                                <option value="{{ $f->Code_Annee_Etude }}" {{ (string)$codeFiliere === (string)$f->Code_Annee_Etude ? 'selected' : '' }}>
                                    {{ $f->Libelle_AnEtude }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-12 text-md-end text-start">
                        <div class="btn-group w-100" role="group">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class=" ri-search-line"></i> Afficher
                            </button>
                            @if ($selectedFiliere && $inscriptions->isNotEmpty())
                                <button type="button" class="btn btn-sm btn-success" onclick="imprimerListePdf()">
                                    <i class=" ri-printer-line"></i> Imprimer PDF
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <!-- Formulaire caché pour l'impression PDF (en POST target _blank) -->
            <form id="formPdf" action="{{ route('liste-etudiants.pdf') }}" method="POST" target="_blank" style="display: none;">
                @csrf
                <input type="hidden" name="anneeAcademique" id="pdfAnneeAcademique" value="{{ $anneeAcademiqueId }}">
                <input type="hidden" name="anneeEtude" id="pdfAnneeEtude" value="{{ $codeFiliere }}">
            </form>
        </div>

        @if ($selectedFiliere)
            @if ($inscriptions->isEmpty())
                <div class="alert alert-info shadow-sm">
                    <i class=" ri-information-line me-1"></i> Aucun étudiant inscrit trouvé pour la filière <b>{{ $selectedFiliere->Libelle_AnEtude }}</b> pour l'année <b>{{ $selectedAnnee?->ANNEE_ACAD }}</b>.
                </div>
            @else
                <!-- Statistiques rapides -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4 col-sm-12">
                        <div class="card border-0 shadow-sm p-3 text-center" style="background-color: #f0fdf4; border-left: 4px solid #198754 !important;">
                            <div class="text-muted small fw-bold text-uppercase">Total Inscrits</div>
                            <div class="fs-3 fw-bold text-success">{{ $nbTotal }}</div>
                            <div class="small text-muted">{{ $selectedFiliere->Libelle_AnEtude }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="card border-0 shadow-sm p-3 text-center" style="background-color: #eff6ff; border-left: 4px solid #0d6efd !important;">
                            <div class="text-muted small fw-bold text-uppercase">Garçons (Masculin)</div>
                            <div class="fs-3 fw-bold text-primary">{{ $nbHommes }}</div>
                            <div class="small text-muted">{{ $nbTotal > 0 ? round(($nbHommes / $nbTotal) * 100, 1) : 0 }} % de l'effectif</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="card border-0 shadow-sm p-3 text-center" style="background-color: #fdf2f8; border-left: 4px solid #d63384 !important;">
                            <div class="text-muted small fw-bold text-uppercase">Filles (Féminin)</div>
                            <div class="fs-3 fw-bold" style="color: #d63384;">{{ $nbFemmes }}</div>
                            <div class="small text-muted">{{ $nbTotal > 0 ? round(($nbFemmes / $nbTotal) * 100, 1) : 0 }} % de l'effectif</div>
                        </div>
                    </div>
                </div>

                <!-- Tableau principal -->
                <div class="card p-3 shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary fs-6">{{ $nbTotal }} étudiants classés par ordre alphabétique (A &rarr; Z)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="filtreRecherche" class="form-control form-control-sm" placeholder="Recherche rapide..." style="max-width: 240px;" onkeyup="filtrerTableau()">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exporterCsv()" title="Télécharger en CSV / Excel">
                                <i class=" ri-file-excel-line"></i> Exporter CSV
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0" id="tableEtudiants" data-ed-bulk style="font-size: 0.9rem;">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width: 5%;">N°</th>
                                    <th style="width: 12%;">Matricule</th>
                                    <th style="width: 30%; text-align: left;">Nom &amp; Prénoms</th>
                                    <th style="width: 8%;">Sexe</th>
                                    <th style="width: 22%; text-align: left;">Date &amp; Lieu de Naissance</th>
                                    <th style="width: 11%;">Nationalité</th>
                                    <th style="width: 12%;">Téléphone</th>
                                    <th style="width: 9%;">Paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inscriptions as $ins)
                                    @php
                                        $etu = $ins->etudiant;
                                        $nom = $etu ? ($etu->nomEtu . ' ' . $etu->prenomsEtu) : $ins->MATRICULE;
                                        $sexe = strtoupper(trim($etu->sexeEtu ?? ''));
                                        $dateNais = !empty($etu->dateNaisEtu) ? date('d/m/Y', strtotime($etu->dateNaisEtu)) : '';
                                        $lieuNais = trim($etu->lieuNaisEtu ?? '');
                                        $naissStr = $dateNais;
                                        if ($lieuNais !== '') {
                                            $naissStr .= ($naissStr ? ' à ' : '') . $lieuNais;
                                        }
                                        $nat = $etu?->nationalite?->libelleNationalite ?? $etu?->codeNationalite ?? '-';
                                        $tel = trim($etu->telEtu ?? '-') ?: '-';
                                    @endphp
                                    <tr data-ed-matricule="{{ $ins->MATRICULE }}">
                                        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                                        <td class="text-center font-monospace fw-bold text-secondary">{{ $ins->MATRICULE }}</td>
                                        <td class="fw-semibold text-dark text-start">
                                            {{ $nom }}
                                        </td>
                                        <td class="text-center">
                                            @if ($sexe === 'M')
                                                <span class="badge bg-primary px-2 py-1">M</span>
                                            @elseif ($sexe === 'F')
                                                <span class="badge px-2 py-1" style="background-color: #d63384;">F</span>
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                        <td class="text-start">
                                            {{ $naissStr ?: '-' }}
                                        </td>
                                        <td class="text-center text-muted">
                                            {{ $nat }}
                                        </td>
                                        <td class="text-center">
                                            @if ($tel !== '-')
                                                <a href="tel:{{ $tel }}" class="text-decoration-none text-dark">
                                                    <i class=" ri-phone-line me-1 text-muted"></i>{{ $tel }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="ed-pay-btn" data-ed-pay="{{ $ins->MATRICULE }}" title="Encaisser un paiement">
                                                <i class="ri-hand-coin-line"></i>Encaisser
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-secondary text-center p-5 shadow-sm">
                <i class=" ri-team-line fs-1 text-muted d-block mb-2"></i>
                <h5 class="fw-bold">Sélectionnez une année académique et une filière</h5>
                <p class="text-muted mb-0">La liste complète des étudiants inscrits s'affichera triée rigoureusement par ordre alphabétique de nom.</p>
            </div>
        @endif
    </div>
</section>

<script>
function imprimerListePdf() {
    var annee = document.getElementById('anneeAcademique').value;
    var filiere = document.getElementById('anneeEtude').value;
    if (!annee || !filiere) {
        alert("Veuillez d'abord sélectionner une filière.");
        return;
    }
    document.getElementById('pdfAnneeAcademique').value = annee;
    document.getElementById('pdfAnneeEtude').value = filiere;
    document.getElementById('formPdf').submit();
}

function filtrerTableau() {
    var input = document.getElementById("filtreRecherche");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tableEtudiants");
    if (!table) return;
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var rowText = tr[i].innerText || tr[i].textContent;
        if (rowText.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

function exporterCsv() {
    var table = document.getElementById("tableEtudiants");
    if (!table) return;
    var rows = table.querySelectorAll("tr");
    var csv = [];
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) {
            var text = cols[j].innerText.replace(/"/g, '""').trim();
            row.push('"' + text + '"');
        }
        csv.push(row.join(";"));
    }
    var csvContent = "\uFEFF" + csv.join("\r\n");
    var blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    var link = document.createElement("a");
    var url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    var filiereNom = document.getElementById("anneeEtude").options[document.getElementById("anneeEtude").selectedIndex].text;
    link.setAttribute("download", "Liste_" + filiereNom.replace(/[^a-zA-Z0-9]/g, "_") + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection
