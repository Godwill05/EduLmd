<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">

<title>Erreur</title>
<meta content="" name="description">
<meta content="" name="keywords">

<!-- Favicons -->
<link href="favicon.ico" rel="icon">
<link href={{asset("/assets/img/apple-touch-icon.png")}} rel="apple-touch-icon">

<!-- Google Fonts -->
<link href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">


<!-- Template Main CSS File -->
<link href="assets/css/style.css" rel="stylesheet">
<link href={{asset("/assets/vendor/bootstrap/css/bootstrap.min.css")}} rel="stylesheet">


</head>

<body style="height: 100vh;font-family: nunito; margin: 0px;padding: 0px;" class="bg-white">

<section style="display: flex; flex-direction: column; text-align: center; justify-content: space-around; align-items: center;">
 <img src="{{asset('/logo.svg')}}" height="200px" width="400px" alt="Page Not Found" style="text-align: center;">
<h1 style="font-size: 180px; font-weight: 700; color: #0576C7; margin: 0;">404</h1>
<h2 style="margin:10px 0px 10px 0px;    font-size: 24px;
font-weight: 700;
color: #012970;">La page que vous cherchez n'existe pas.</h2>

<div style="margin: 50px;"><a href="{{ route('home') }}" style="text-decoration:none; background-color:#033086; color: #fff; padding: 12px 30px; border-radius: 5px;font-size: 18px;">Retour à l'accueil</a></div>



</div>
</section>
</body>

</html>
