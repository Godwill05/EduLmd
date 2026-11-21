<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reçu de paiement</title>
  <style>
    /* Styles de base locaux (remplace l'ancien import Bootstrap, rendu identique) */
    .container { width: 100%; max-width: 1140px; margin: 0 auto; padding: 0 12px; }
    .table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
    .table th, .table td { padding: 0.25rem 0.5rem; vertical-align: top; }
    .table-bordered th, .table-bordered td { border: 1px solid #0B0B0C; }
    .border-dark { border-color: #0B0B0C !important; }
    .border { border: 1px solid #0B0B0C; }
    .border-1 { border-width: 1px; }
    body { margin: 0; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; font-size: 14px; color: #212529; }
  </style>
  <style>
    *

    .first {
      /* border: 1px solid yellow; */

      /* padding-top: 35px; */
      margin-top: 0.5%;
      margin-bottom: 0.5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .first img {
      padding: 15px;
      text-align: center;
      height: 100%;
      width: 14%;
      /* border: 1px solid green; */
    }

    .ecrit {


      text-align: center;
      height: 100%;
      width: 75%;
      /* border: 1px solid red; */
    }

    .title-receipt {
      background-color: red;

    }

    .receipt-table th {
      margin: 0%;
      background-color: #680210;
      color: white;
      padding: 1px;
      text-align: left;
    }


    .receipt-table td {
      /* background-color:#ccc; */
      padding: 1px;
      text-align: left;
    }
  </style>
</head>

<body>
    <div class="container">

        <!-- <header style="border: 1px solid red; height: 90px; display: flex; justify-content: space-between;">
              <div style="width: 15%;"><img src="logo.png" style="height: 50px;" alt=""></div>
              <div style="width: 70%;"><h3>REPUBLIQUE DU BENIN</h3>
                <p>MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE
                (MESRS)-----*-----*-----*----</p>
              <p>INSTITUT UNIVERSITAIRE</p>
              <p style="position: absolute;">INSTITUT UNIVERSITAIRE SAINTS ADRIEN ET ANSELME</p>
              </div>
              <div style="width: 15px;"><img src="logo.png" style="height: 50px;" alt=""></div>
          </header> -->

        <div class="first">
          <img src="logo.png" alt="" />
          <div class="ecrit" style="line-height: 15px;">
            <h6 style=" font-size: 12px;margin: 0% 0%;">REPUBLIQUE DU BENIN</h6>-----*-----*-----*----
            <h6 style="font-size: 12px;;margin: 0% 0%;">
              MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE
              (MESRS)
            </h6>-----*-----*-----*----
            <h6 style="font-size: 12px; margin: 0% 0%;">
              INSTITUT UNIVERSITAIRE
            </h6>-----*-----*-----*----
            <h6 style="font-size: 12px; margin: 0% 0%;">INSTITUT UNIVERSITAIRE SAINTS ADRIEN ET ANSELME</h6>
          </div>
          <img src="logo.png" alt="" />
        </div>

        <h3 style="margin:1.5% 0% 0.5% 0%; font-size: 13px; text-align: center;"><span>ANNEE: 2022-2023</span> | <span>FILIERE: IG</span></h3>
        <h3 style="margin:0.5% 0% 0.5% 0%; font-size: 13px; text-align: center;">Recapitulatif total des paiements</h3>
        <table class="table table-bordered border-dark" style="width: 100%; ">
          <thead>
              <tr class="border border-1">
                <th>Matricule</th>
                <th>Nom </th>
                <th>Prénom(s)</th>
                <th>Montant à payer</th>
                <th>Cumul paiement</th>
                <th>Reste à payer</th>
              </tr>
          </thead>
          <tbody>
              @foreach ($items as $item)
                  <tr class='border border-1'>
                  <td>{{$item->MATRICULE}}</td>
                  <td>{{$item->etudiant->nomEtu}}</td>
                  <td>{{$item->etudiant->prenomsEtu}}</td>
                  <td>{{$item->frais_paye}}</td>
                  <td>{{$item->paiements()->sum('montantPaye')}}</td>
                  <td>{{$item->reste_paye}}</td>
                  </tr>
              @endforeach
          </tbody>
        </table>
    </div>
</body>

</html>
