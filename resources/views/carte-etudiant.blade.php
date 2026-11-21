<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Etudiant</title>
</head>

<body>
<style>
*{
	color:#000000
}
</style>

    <div style="text-align: center; width: 100mm;height: 72mm; margin-top: 4px; margin-left: 4px" >
        <div id="card-header"
            style="height: 25% ; border: 1px solid #FCDA79; background-color: white; text-align: center; display: flex; justify-content: start;align-items: center; padding-left: 5px;">
            <div style="margin-left: 10px;">

                <!-- <h1 style="margin-top: 0px; color: #680210; margin: 0px;">IUSAA</h2> -->
                <h2 style="letter-spacing: 7.3px;margin-top: 0px; color: #680210; margin: 0px; font-size: 12px;font-weight:bold;">INSTITUT UNIVERSITAIRE</h2>
                <div style="height: 2px; border: 2px solid #680210; background-color: #680210;"></div>
                <h3 style="letter-spacing: 7.3px;margin-top: 0px; color: #680210; margin: 0px;font-size: 12px;font-weight:bold">SAINTS ADRIEN ET ANSELME</h3>
            </div>
        </div>
        <P style="margin: 0px;border: 1px solid #FCDA79; background-color: #FCDA79; padding: 1.5px; font-size: 12px; color: #680210; font-weight: bold;"> CARTE D'ETUDIANT / STUDENT CARD</p>
        <div id="card-body"
            style="border: 1px solid #FCDA79;  display: flex;align-items: center; padding: 0px 0px 5px 5px;">
            <div id="card-image"
                style="text-align: center; width: 33%; margin-top: 0; padding-top: 5px;">
                <h6 style="margin-top: -22px">Matricule (ID)</h6>
                <h6 style="font-weight: bold;margin-bottom: 10px;">{{$inscription->etudiant->MATRICULE}}</h6>
                <img
                    @isset($inscription->etudiant->photo)
                    src="/storage/assets/img/etudiants/{{$inscription->etudiant->photo}}"
                    @else
                    src="/storage/assets/img/etudiants/{{($inscription->etudiant->sexeEtu='M')?'M.png':'Mlle.png'}}"
                    @endisset
                    alt="Photo de l'étudiant" height="90px" width="90px"
                    style="border: 5px groove #FCDA79; border-radius: 25px;
                    -webkit-border-radius: 25px;
                    -moz-border-radius: 25px;">
            </div>

            <div class="card-info"  style="text-align: left; width: 76%; padding-bottom: 10px;">
                <div id="name" style="text-align: center;margin-bottom: 25px; margin-top: 5px;">
                    <h6 style="font-weight: bold">{{$inscription->etudiant->nomEtu}}</h6>
                    <h6 style="font-weight: bold">{{$inscription->etudiant->prenomsEtu}}</h6>
                </div>

                <p style="line-height: 2px;padding-left: 40px; text-align: justify;">
                    <label style="font-size: 10px">Né(e) / Born :</label> <label style="font-weight: bold;font-size: 10px"> {{\Carbon\Carbon::parse($inscription->etudiant->dateNaisEtu)->format('d/m/Y')}}</label><br>
                    <label style="font-size: 10px">A / At                 </label> : <label style="font-weight: bold;font-size: 10px"> {{$inscription->etudiant->lieuNaisEtu}}</label><br>
                    <label style="font-size: 10px">Pays d'origine / Country</label> : <label style="font-weight: bold;font-size: 10px"> {{$inscription->etudiant->nationalite->libelleNationalite ?? 'Non spécifié'}}</label><br>
                    <label style="font-size: 10px">Téléphone / Phone</label> : <label style="font-weight: bold;font-size: 10px"> {{$inscription->etudiant->telEtu}}</label><br>
                    <label style="font-size: 10px">Spécialité / Field</label> : <label style="font-weight: bold;font-size: 10px"> {{$inscription->anneeEtude->Libelle_AnEtude}}</label><br>
                    <label style="font-size: 10px">Année académique</label> : <label style="font-weight: bold;font-size: 10px"> {{$inscription->anneeacademique->ANNEE_ACAD}}</label><br>
                </p>
            </div>


        </div>
    </div>
    <div style="text-align: center; width: 100mm;height: 72mm; border: 1px solid #FCDA79;margin-top: 6px;margin-left: 4px">
        <div id="card-header"
            style="height: 25% ; border: 1px solid #FCDA79;border-bottom: 0px; background-color: white; text-align: center; ">
            <img src="/assets/img/iusaa.png" alt="Photo de l'étudiant" height="70px" width="70px">

        </div>
        <P style=" font-size: 12px; color: #680210; font-weight: bold; padding: 2px;">INSTITUT UNIVERSITAIRE SAINTS
            ADRIEN ET ANSELME</p>
        <p style="border: 1px solid #FCDA79; background-color: #FCDA79; font-size: 16px; color: #680210; font-weight: bold;">
            CARTE D'ETUDIANT / STUDENT CARD</p>
            <p style="font-size: 12px; font-style: italic;margin-top: -13px">Cette carte est valable pour l'année académique {{$inscription->anneeacademique->ANNEE_ACAD}}</p>
        <div id="signature" style="height: auto;border: 1px solid #FCDA79; font-size: 12px;padding: 5px; margin-top: -17px">
            <p>La Directrice des Etudes</p>

            <img src="/assets/signature.png" alt="signature" height="50px">
            <p>
                Dr Erasme ARINLOYE
            </p>
        </div>

    </div>

</body>

</html>
