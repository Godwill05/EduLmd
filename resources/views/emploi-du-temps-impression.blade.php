@php
  use App\Support\EmploiDuTemps;
  $jours = EmploiDuTemps::JOURS;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Emploi du temps — {{ $classe->Nom_Classe }}</title>
  <style>
    /* Feuille autonome : pas de dépendance externe, pas de CDN.
       L'impression doit fonctionner même si le réseau est coupé. */
    * { box-sizing: border-box; }
    body { margin: 0; padding: 12mm 10mm; font-family: "Segoe UI", Arial, sans-serif;
           color: #111; font-size: 11px; }

    header { display: flex; align-items: center; gap: 10mm; margin-bottom: 6mm;
             padding-bottom: 4mm; border-bottom: 2px solid #822A40; }
    header img { height: 18mm; }
    header .etab { font-size: 13px; font-weight: 700; }
    header .sous { font-size: 10px; color: #666; }
    header .titre { margin-left: auto; text-align: right; }
    header .titre h1 { margin: 0; font-size: 16px; text-transform: uppercase;
                       letter-spacing: .04em; color: #822A40; }
    header .titre .classe { font-size: 20px; font-weight: 800; }
    header .titre .periode { font-size: 10px; color: #666; }

    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #C9C9CF; padding: 2mm 1.5mm; vertical-align: top; }
    thead th { background: #822A40; color: #fff; font-size: 10px; text-transform: uppercase;
               letter-spacing: .05em; text-align: center; padding: 2mm 1mm; }
    thead th:first-child { width: 14mm; background: #6B2134; }

    td.heure { background: #F7F7F9; text-align: center; font-weight: 700;
               font-size: 10px; vertical-align: middle; }

    .seance { margin-bottom: 1.4mm; }
    .seance:last-child { margin-bottom: 0; }
    .seance .ecue { font-weight: 700; font-size: 10px; }
    .seance .detail { font-size: 9px; color: #555; }
    .seance .salle { font-weight: 700; color: #822A40; }
    .seance .periode { font-size: 8px; color: #888; }

    footer { margin-top: 5mm; display: flex; justify-content: space-between;
             font-size: 9px; color: #666; }
    footer .coordonnees { text-align: right; }

    @page { size: A4 landscape; margin: 8mm; }

    /* L'impression est autonome : elle n'hérite pas du thème de
       l'application. C'est voulu — un emploi du temps s'imprime sur
       papier blanc, quel que soit le réglage d'écran de l'utilisateur.
       On force donc explicitement le fond clair, au cas où. */
    html { background: #fff; color-scheme: light; }

    /* À l'impression : on coupe les éléments d'interface, on garde les
       bordures (par défaut, les navigateurs les suppriment). */
    @media print {
      body { padding: 0; }
      table { page-break-inside: auto; }
      tr { page-break-inside: avoid; }
      thead { display: table-header-group; }
    }
  </style>
</head>
<body>

  <header>
    @if (file_exists(public_path('logo6.png')))
      <img src="{{ asset('logo6.png') }}" alt="">
    @endif
    <div>
      {{-- Les données de l'établissement sont dans la table `ufr` :
           la raison sociale est `Ecol_Ufr`, pas `Nom_Ufr`. --}}
      <div class="etab">{{ $etablissement->Ecol_Ufr ?? 'Institut Universitaire' }}</div>
      <div class="sous">{{ $etablissement->ville ?? '' }}</div>
    </div>
    <div class="titre">
      <h1>Emploi du temps</h1>
      <div class="classe">{{ $classe->Nom_Classe }}</div>
      <div class="periode">
        @if ($du || $au)
          Du {{ $du ?: '…' }} au {{ $au ?: '…' }}
        @else
          Toutes périodes confondues
        @endif
        — {{ $grille['total'] }} séance(s)
      </div>
    </div>
  </header>

  <table>
    <thead>
      <tr>
        <th>Heure</th>
        @foreach ($jours as $jour)
          <th>{{ $jour }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @if ($grille['total'] === 0)
        <tr>
          <td colspan="8" style="text-align:center;padding:10mm;color:#888;">
            Aucun créneau saisi pour cette classe.
          </td>
        </tr>
      @else
        <tr>
          <td class="heure">{{ $grille['depart'] }}</td>
          @foreach (array_keys($jours) as $num)
            <td>
              @forelse ($grille['grille'][$num][$grille['depart']] ?? [] as $s)
                <div class="seance">
                  <div class="ecue">{{ $s['libelle_ecue'] }}</div>
                  <div class="detail">{{ $s['ue'] }} — <span class="salle">{{ $s['libelle_salle'] }}</span></div>
                  <div class="periode">
                    {{ \Carbon\Carbon::parse($s['du'])->format('d/m') }} → {{ \Carbon\Carbon::parse($s['au'])->format('d/m/Y') }}
                  </div>
                </div>
              @empty
                <span style="color:#CCC;">—</span>
              @endforelse
            </td>
          @endforeach
        </tr>
      @endif
    </tbody>
  </table>

  <footer>
    <div>
      Document généré le {{ now()->format('d/m/Y à H:i') }}.
      Les créneaux peuvent évoluer : vérifiez l'affichage en ligne.
    </div>
    <div class="coordonnees">
      @if (! empty($etablissement->Tel)) Tél. {{ $etablissement->Tel }} @endif
      @if (! empty($etablissement->mail)) — {{ $etablissement->mail }} @endif
      @if (! empty($etablissement->Site_web)) — {{ $etablissement->Site_web }} @endif
    </div>
  </footer>

</body>
</html>
