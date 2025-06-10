<?php
// Suprimir errores en producción
error_reporting(0);
ini_set('display_errors', 0);

require_once 'backend/config/database.php';

// Verificar si el usuario está autenticado
$user = validateSession();
$isAuthenticated = $user !== false;

// Obtener productos para mostrar
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT p.idProducto, p.nombre, p.descripcion, p.precio, p.stock, 
                     c.nombre as categoria_nombre
              FROM producto p 
              LEFT JOIN categoria c ON p.idCategoria = c.idCategoria 
              WHERE p.stock > 0
              ORDER BY p.nombre ASC LIMIT 8";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Obtener favoritos del usuario si está autenticado
    $userFavorites = [];
    if ($isAuthenticated) {
        $query_fav = "SELECT idProducto FROM favoritos WHERE idUsuario = :user_id";
        $stmt_fav = $db->prepare($query_fav);
        $stmt_fav->bindParam(':user_id', $user['id']);
        $stmt_fav->execute();
        
        while ($row = $stmt_fav->fetch(PDO::FETCH_ASSOC)) {
            $userFavorites[] = (int)$row['idProducto'];
        }
    }
} catch (Exception $e) {
    $products = [];
    $userFavorites = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blue - Lo mejor para tí</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/header/betterfaviconblue1.png"/>
    
    <style>
        /* Reset y configuración base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #333;
            font-family: "Poppins", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            display: block;
            max-width: 100%;
        }

        /* Variables CSS */
        :root {
            --header-height: 106px;
            --header-mobile-height: 70px;
            --header-bg: #ffffff;
            --header-border: #eee;
            --menu-bg: #ffffff;
            --menu-hover: #f0f0f0;
            --menu-active: #e0e0e0;
            --menu-text: #333333;
            --menu-icon: #666666;
            --transition-speed: 0.3s;
            --color-principal: #e0f7fa;
            --color-blanco: #ffffff;
            --color-texto: #333333;
            --color-gris-claro: #f5f5f5;
            --color-gris-borde: #eeeeee;
            --color-secundario: #a5d8a7;
            --color-amarillo: #fff59d;
            --border-radius-small: 5px;
            --border-radius-large: 15px;
            --box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* Header Styles */
        .HeaderSite {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px;
            height: var(--header-height);
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--header-border);
            box-sizing: border-box;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            transition: height var(--transition-speed) ease;
        }

        .Logo img {
            height: 80px;
            width: 260px;
            object-fit: contain;
            transition: height var(--transition-speed) ease, width var(--transition-speed) ease;
        }

        .HeaderIcons {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .CircularButtons {
            width: 54px;
            height: 54px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .CircularButtons:hover {
            background-color: #e0e0e0;
        }

        .CircularButtons .Link {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .CircularButtons img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        /* Menu toggle button */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 1010;
        }

        .menu-toggle .bar {
            display: block;
            width: 24px;
            height: 2px;
            background-color: var(--menu-text);
            margin: 5px 0;
            transition: all var(--transition-speed) ease;
        }

        /* Mobile menu overlay */
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
        }

        .mobile-menu-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Hero Section */
        .heroSection {
            background-image: url('img/main/pruebaaa.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 630px;
            position: relative;
            display: flex;
            align-items: center;
            padding-left: 8%;
            box-sizing: border-box;
            margin-top: var(--header-height);
            transition: margin-top var(--transition-speed) ease, height var(--transition-speed) ease;
        }

        .Exp {
            position: static;
            transform: none;
        }

        .heroContent {
            max-width: 480px;
            color: #333;
            padding: 20px;
        }

        .heroContent h1 {
            font-size: 3.8em;
            font-weight: bold;
            color: #333;
            margin-top: 0;
            margin-bottom: 0.2em;
        }

        .heroContent .subtitle {
            font-size: 1.6em;
            color: #333;
            margin-bottom: 1em;
            line-height: 1.4;
        }

        .heroContent .ctaText {
            font-size: 1.4em;
            font-weight: bold;
            color: #333;
            margin-bottom: 2em;
        }

        .ctaButton {
            display: inline-block;
            background-color: #a5d6a7;
            color: #f3ecec;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 28px;
            font-weight: bold;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .ctaButton:hover {
            background-color: #96c698;
            color: #333;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Categories */
        .categoriesContainer {
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            padding: 60px 20px;
            text-align: center;
            background-color: #fff;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 40px;
        }

        .subcategoriesContainer {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 130px;
        }

        .CategoriesCircularButtons {
            width: 80px;
            height: 80px;
            background-color: #f5f5f5;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.2s ease;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
        }

        .CategoriesCircularButtons:hover {
            background-color: #e8e8e8;
        }

        .CategoriesCircularButtons img {
            width: 35px;
            height: 35px;
            object-fit: contain;
        }

        .subcategoriesContainer .TextualButtons {
            width: auto;
            text-align: center;
        }

        .subcategoriesContainer .TextualButtons .Link {
            color: #333;
            font-weight: 500;
            font-size: 16px;
        }

        .subcategoriesContainer .TextualButtons .Link:hover {
            color: #0d47a1;
        }

        /* Products Section */
        .products-section {
            text-align: center;
            margin-bottom: 40px;
            margin-top: 40px;
        }

        .productSection.sectionBlue {
            background-color: rgba(224, 247, 250, 0.3);
            border-radius: 30px;
            margin-bottom: 60px;
            padding: 30px 20px;
        }

        .section-title {
            background-color: var(--color-gris-claro);
            color: var(--color-texto);
            font-size: 1.5rem;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 40px;
        }

        .sectionBlue .sectionTitle {
            background-color: transparent;
            color: var(--color-texto);
            text-align: center;
            margin: 20px auto 40px auto;
            display: block;
            width: fit-content;
        }

        #pInteresarteTitle {
            color: #02386e;
        }

        #cFacialTitle {
            color: #02386e;
        }

        #pdestacados {
            background-color: #97dcca;
            color: #ffffff;
            display: block;
            width: calc(100% + 40px);
            position: relative;
            left: -20px;
            transform: none;
            padding: 10px 20px;
            text-align: center;
            box-sizing: border-box;
            border-radius: var(--border-radius-small);
        }

        .container #pdestacados {
            width: 100%;
            left: 0;
        }

        /* Products Container */
        .products-container {
            width: 100%;
            max-width: 923px;
            min-height: 380px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 18px;
            padding: 20px;
            background-color: #f0f0f0;
            border-radius: 12px;
        }

        #pinteresarte {
            background-color: #97dcca;
        }

        #facialCare {
            background-color: #a1d7e6;
        }

        /* Product Cards */
        .product-card {
            width: 214px;
            min-height: 340px;
            background-color: #d0d0d0;
            border-radius: 8px;
            padding: 7px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            box-sizing: border-box;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .product-card-inner {
            width: 100%;
            min-height: 320px;
            background-color: #ffffff;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            flex-grow: 1;
            transition: box-shadow 0.3s ease;
        }

        .product-card:hover .product-card-inner {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .product-image {
            width: calc(100% - 18px);
            height: 178px;
            background-color: #e8e8e8;
            margin: 9px auto 0;
            border-radius: 4px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }

        .favorite-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 39px;
            height: 39px;
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #666;
        }

        .favorite-btn:hover {
            background-color: #fff;
            color: #e74c3c;
            transform: scale(1.05);
        }

        .favorite-btn.active {
            background-color: #e74c3c;
            color: #fff;
            border-color: #e74c3c;
        }

        .product-info {
            padding: 12px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
            min-height: 120px;
        }

        .product-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.3;
            flex-shrink: 0;
        }

        .product-price {
            font-size: 1rem;
            font-weight: 600;
            color: #1f202e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 8px;
            flex-shrink: 0;
        }

        .btn-buy-now,
        .btn-add-cart {
            width: 52px;
            height: 28px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .btn-buy-now {
            background-color: #a5d6a7;
            color: #fff;
        }

        .btn-buy-now:hover {
            background-color: #81c784;
            transform: translateY(-1px);
        }

        .btn-add-cart {
            background-color: #fff59d;
            color: #333;
        }

        .btn-add-cart:hover {
            background-color: #ffeb3b;
            transform: translateY(-1px);
        }

        .btn-buy-now:active,
        .btn-add-cart:active {
            transform: translateY(0);
        }

        /* Final Section */
        .finalfield {
            background-color: transparent;
            padding: 60px 20px;
            margin-top: 60px;
            margin-bottom: 0;
        }

        .finalMesagge {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 60px;
        }

        .maleImage {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .maleImage img {
            width: 302px;
            height: 280px;
            object-fit: contain;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .textFinalMessage {
            flex-grow: 1;
            text-align: left;
            padding-left: 40px;
        }

        .contactUs {
            margin-bottom: 15px;
        }

        .contactUs p {
            font-size: 2.2rem;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
            margin: 0;
            font-family: "Poppins", sans-serif;
        }

        /* Footer */
        footer {
            background-color: #ccd4df;
            padding: 40px 40px 20px 40px;
            color: #333;
            font-family: "Poppins", sans-serif;
            box-sizing: border-box;
        }

        .FooterTextLinks {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px 40px;
            width: 100%;
            align-items: start;
        }

        .contactField .textoA1 p {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 10px;
            color: #000000;
        }

        .contactField .textoA2 p {
            font-size: 16px;
            margin-top: 0;
            margin-bottom: 15px;
            color: #333333;
        }

        .footerCopyright {
            font-size: 14px;
            color: #555555;
            margin-top: 15px;
            line-height: 1.4;
        }

        .linkFooterFields {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .linkFooterFields .TextualButtons {
            width: auto;
            text-align: left;
        }

        .linkFooterFields .TextualButtons .Link {
            font-size: 16px;
            font-weight: 400;
            color: #333333;
            text-decoration: none;
        }

        .linkFooterFields .TextualButtons .Link:hover {
            text-decoration: underline;
        }

        .socialMediaField {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .socialMediaField .textoA2 p {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 15px;
            color: #000000;
            text-align: center;
        }

        .socialMediaContainer {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 5px;
        }

        .socialMediaButtons a {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background-color: #ffffff;
            border-radius: 50%;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .socialMediaButtons a:hover {
            transform: translateY(-2px);
        }

        .socialMediaButtons a img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        /* Notifications */
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            width: 100%;
            pointer-events: none;
        }

        .notification {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transform: translateX(120%);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            pointer-events: auto;
            max-width: 100%;
            overflow: hidden;
            position: relative;
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .notification.hide {
            transform: translateX(120%);
            opacity: 0;
        }

        .notification-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-content {
            flex-grow: 1;
            overflow: hidden;
        }

        .notification-title {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .notification-message {
            margin: 0;
            font-size: 14px;
            color: #666;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .notification-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #999;
            padding: 4px;
            line-height: 1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            transition: background-color 0.2s ease;
        }

        .notification-close:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: #666;
        }

        .notification-success {
            border-left: 4px solid #4caf50;
        }

        .notification-success .notification-icon {
            color: #4caf50;
        }

        .notification-error {
            border-left: 4px solid #f44336;
        }

        .notification-error .notification-icon {
            color: #f44336;
        }

        .notification-warning {
            border-left: 4px solid #ff9800;
        }

        .notification-warning .notification-icon {
            color: #ff9800;
        }

        .notification-info {
            border-left: 4px solid #2196f3;
        }

        .notification-info .notification-icon {
            color: #2196f3;
        }

        /* Search Panel */
        .search-panel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 9000;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .search-panel.active {
            opacity: 1;
            visibility: visible;
        }

        .search-container {
            width: 100%;
            max-width: 700px;
            margin-top: 120px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: translateY(-30px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .search-panel.active .search-container {
            transform: translateY(0);
            opacity: 1;
        }

        .search-header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        #search-input {
            flex: 1;
            border: none;
            font-size: 18px;
            padding: 10px 0;
            outline: none;
            font-family: "Poppins", sans-serif;
            color: #333;
        }

        #search-input::placeholder {
            color: #aaa;
        }

        #search-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 5px 10px;
            margin-left: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        #search-close:hover {
            background-color: #f0f0f0;
            color: #333;
        }

        .search-results {
            max-height: 60vh;
            overflow-y: auto;
            padding: 0;
        }

        .search-message {
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .HeaderSite {
                height: var(--header-mobile-height);
                padding: 0 15px;
            }

            .Logo img {
                height: 45px;
                width: auto;
            }

            .menu-toggle {
                display: block;
                order: 1;
            }

            .Nav {
                position: fixed;
                top: 0;
                right: -280px;
                left: auto;
                width: 280px;
                height: 100%;
                background-color: var(--menu-bg);
                z-index: 1000;
                transition: right var(--transition-speed) ease;
                box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
                overflow-y: auto;
                padding-top: 0;
            }

            .Nav.active {
                right: 0;
            }

            .HeaderIcons {
                display: flex;
                flex-direction: column;
                gap: 0;
                padding: 0;
                margin-top: 0;
            }

            .CircularButtons {
                width: 100%;
                height: auto;
                background-color: transparent;
                border-radius: 0;
                border-bottom: 1px solid var(--header-border);
            }

            .CircularButtons:last-child {
                border-bottom: none;
            }

            .CircularButtons .Link {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 15px;
                padding: 15px 20px;
                border-radius: 0;
                width: 100%;
                background-color: transparent;
                transition: background-color var(--transition-speed) ease;
                text-decoration: none;
                box-sizing: border-box;
            }

            .CircularButtons .Link:hover {
                background-color: var(--menu-hover);
            }

            .CircularButtons .Link:active {
                background-color: var(--menu-active);
            }

            .CircularButtons img {
                width: 22px;
                height: 22px;
                object-fit: contain;
            }

            .CircularButtons .Link::after {
                content: attr(title);
                font-size: 16px;
                font-weight: 500;
                color: var(--menu-text);
                text-align: left;
                flex-grow: 1;
            }

            .menu-toggle.active .bar:nth-child(1) {
                transform: translateY(7px) rotate(45deg);
            }

            .menu-toggle.active .bar:nth-child(2) {
                opacity: 0;
            }

            .menu-toggle.active .bar:nth-child(3) {
                transform: translateY(-7px) rotate(-45deg);
            }

            .heroSection {
                margin-top: var(--header-mobile-height);
                padding: 30px 20px;
                height: auto;
                min-height: 450px;
                background-size: cover;
                background-position: center right;
                justify-content: flex-start;
                align-items: center;
                display: flex;
            }

            .heroContent {
                max-width: 100%;
                width: 100%;
                padding: 20px;
                text-align: left;
                background: transparent;
                border-radius: 15px;
            }

            .heroContent h1,
            .heroContent .subtitle,
            .heroContent .ctaText {
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            }

            .heroContent h1 { 
                font-size: 2.5em; 
                margin-bottom: 0.3em;
                line-height: 1.1;
            }
            .heroContent .subtitle { 
                font-size: 1.3em; 
                margin-bottom: 1em;
                line-height: 1.3;
            }
            .heroContent .ctaText { 
                font-size: 1.1em; 
                margin-bottom: 1.8em;
                font-weight: 600;
            }
            .ctaButton { 
                font-size: 1em; 
                padding: 14px 28px;
                display: inline-block;
                min-width: 150px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .heroSection {
                min-height: 400px;
                padding: 20px 15px;
                background-position: center right;
            }

            .heroContent {
                max-width: 100%;
                padding: 18px;
                margin-right: 0;
                background: transparent;
            }

            .heroContent h1,
            .heroContent .subtitle,
            .heroContent .ctaText {
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            }

            .heroContent h1 { 
                font-size: 2em; 
                line-height: 1.1;
            }
            .heroContent .subtitle { 
                font-size: 1.1em; 
                line-height: 1.3;
            }
            .heroContent .ctaText { 
                font-size: 1em;
                margin-bottom: 1.5em;
            }
            .ctaButton { 
                font-size: 0.9em; 
                padding: 12px 24px;
                width: 100%;
                max-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px 10px;
            }

            #pdestacados {
                width: calc(100% + 20px);
                left: -10px;
            }

            .container #pdestacados {
                width: 100%;
                left: 0;
            }

            .products-container {
                gap: 10px;
                padding: 10px;
            }

            .heroSection {
                min-height: 280px;
                padding: 15px 10px;
            }

            .heroContent {
                max-width: 65%;
                padding: 5px;
            }

            .heroContent h1 { font-size: 1.7em; }
            .heroContent .subtitle { font-size: 0.95em; }
            .heroContent .ctaText { font-size: 0.85em; }
            .ctaButton { font-size: 0.85em; padding: 8px 18px;}

            .finalfield {
                padding: 30px 15px;
            }

            .maleImage img {
                width: 180px;
                padding: 10px;
            }

            .contactUs p {
                font-size: 1.4rem;
                line-height: 1.4;
            }

            .subcategoriesContainer {
                width: 75px;
            }

            .CategoriesCircularButtons {
                width: 50px;
                height: 50px;
            }

            .categoriesContainer .subcategoriesContainer .CategoriesCircularButtons img {
                width: 30px;
                height: 30px;
            }

            .subcategoriesContainer .TextualButtons .Link {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div>
        <header class="HeaderSite">
            <div class="Logo">
                <a href="index.php" title="Blue">
                    <img src="img/header/betterblueLogoNoBackground.png" alt="Logo de Blue"/>
                </a>
            </div>
            <nav class="Nav">
                <div>
                    <div class="HeaderIcons">
                        <div class="CircularButtons">
                            <a href="#" title="Buscar" class="Link">
                                <img src="img/header/lupaBuscar.png" alt="Botón de Búsqueda"/>
                            </a>
                        </div>
                        <div class="CircularButtons">
                            <a href="perfil.php#favorites" title="Destacados" class="Link">
                                <img src="img/header/corazonGuardado.png" alt="Botón de Destacados"/>
                            </a>
                        </div>
                        <div class="CircularButtons">
                            <a href="carrito.php" title="Carrito de Compra" class="Link">
                                <img src="img/header/carritoDeCompra.png" alt="Botón de Carrito de Compra"/>
                            </a>
                        </div>
                        <div class="CircularButtons">
                            <a href="perfil.php#notifications" title="Notificaciones" class="Link">
                                <img src="img/header/campanaNotificacion.png" alt="Botón notificaciones"/>
                            </a>
                        </div>
                        <div class="CircularButtons">
                            <?php if ($isAuthenticated): ?>
                                <a href="perfil.php" title="<?= htmlspecialchars($user['name']) ?>" class="Link">
                                    <img src="img/header/usuarioPerfil.png" alt="Perfil de <?= htmlspecialchars($user['name']) ?>"/>
                                </a>
                            <?php else: ?>
                                <a href="login.php" title="Iniciar Sesión" class="Link">
                                    <img src="img/header/usuarioPerfil.png" alt="Iniciar Sesión"/>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <section class="heroSection">
            <div class="Exp">
                <div class="heroContent">
                    <h1 id="blueblue">Blue</h1>
                    <p class="subtitle">El entorno digital ideal para tu negocio</p>
                    <p class="ctaText">¡Contáctanos!</p>
                    <div class="ctaContainer">
                        <a href="#" class="ctaButton" onclick="showDemoNotification()">Clic aquí</a>
                    </div>
                </div>
            </div>                
        </section>

        <main id="productos" class="container">
            <section class="categoriesContainer">
                <div class="subcategoriesContainer">
                    <div class="CategoriesCircularButtons">
                        <a href="#medicina" title="Medicina" class="Link">
                            <img src="img/main/botiquinMedicina.png" alt="Botón de categoría 'Medicina'"/>
                        </a>
                    </div>
                    <div class="TextualButtons">
                        <a href="#medicina" class="Link">Medicina</a> 
                    </div>
                </div>
                <div class="subcategoriesContainer">
                    <div class="CategoriesCircularButtons">
                        <a href="#cuidadopersonal" title="Cuidado personal" class="Link">
                            <img src="img/main/mujerCuidadoPersonal2.png" alt="Botón de categoría 'Cuidado personal'"/>
                        </a>
                    </div>
                    <div class="TextualButtons">
                        <a href="#cuidadopersonal" class="Link">Cuidado personal</a> 
                    </div>
                </div>
                <div class="subcategoriesContainer">
                    <div class="CategoriesCircularButtons">
                        <a href="#sanidad" title="Sanidad" class="Link">
                            <img src="img/main/sanidad.png" alt="Botón de categoría 'Sanidad'"/>
                        </a>
                    </div>
                    <div class="TextualButtons">
                        <a href="#sanidad" class="Link">Sanidad</a> 
                    </div>
                </div>
                <div class="subcategoriesContainer">
                    <div class="CategoriesCircularButtons">
                        <a href="#cuidadodelbebe" title="Cuidado del bebé" class="Link">
                            <img src="img/main/bebeCuidadoDelBebe.png" alt="Botón de categoría 'Cuidado del bebé'"/>
                        </a>
                    </div>
                    <div class="TextualButtons">
                        <a href="#cuidadodelbebe" class="Link">Cuidado del bebé</a> 
                    </div>
                </div>
                <div class="subcategoriesContainer">
                    <div class="CategoriesCircularButtons">
                        <a href="#saludsexual" title="Salud sexual" class="Link">
                            <img src="img/main/parejaCuidadoDeLaPereja2.png" alt="Botón de categoría 'Salud sexual'"/>
                        </a>
                    </div>
                    <div class="TextualButtons">
                        <a href="#saludsexual" class="Link">Salud sexual</a> 
                    </div>
                </div>
            </section>

            <section class="products-section">
                <h2 class="section-title">¡Podría interesarte!</h2>
                
                <div id="pinteresarte" class="products-container">
                    <?php foreach (array_slice($products, 0, 4) as $product): ?>
                    <article class="product-card" data-product-id="<?= $product['idProducto'] ?>">
                        <div class="product-card-inner">
                            <div class="product-image">
                                <img src="img/main/placeholder-product.png" alt="<?= htmlspecialchars($product['nombre']) ?>">
                                <button class="favorite-btn <?= in_array($product['idProducto'], $userFavorites ?? []) ? 'active' : '' ?>" 
                                        aria-label="<?= in_array($product['idProducto'], $userFavorites ?? []) ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>" 
                                        data-product-id="<?= $product['idProducto'] ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><a href="#producto-<?= $product['idProducto'] ?>"><?= htmlspecialchars($product['nombre']) ?></a></h3>
                                <p class="product-price">$<?= number_format($product['precio'], 0, ',', '.') ?></p>
                                <div class="product-actions">
                                    <button class="btn-buy-now" aria-label="Comprar ahora">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="9" cy="21" r="1"></circle>
                                            <circle cx="20" cy="21" r="1"></circle>
                                            <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                        </svg>
                                    </button>
                                    <button class="btn-add-cart" aria-label="Añadir al carrito">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14M5 12h14"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="productSection sectionBlue"> 
                <h2 id="cFacialTitle" class="sectionTitle">Cuidado facial</h2>
                <div id="facialCare" class="products-container">
                    <?php foreach (array_slice($products, 0, 4) as $product): ?>
                    <article class="product-card" data-product-id="<?= $product['idProducto'] ?>">
                        <div class="product-card-inner">
                            <div class="product-image">
                                <img src="img/main/placeholder-product.png" alt="<?= htmlspecialchars($product['nombre']) ?>">
                                <button class="favorite-btn <?= in_array($product['idProducto'], $userFavorites ?? []) ? 'active' : '' ?>" 
                                        aria-label="<?= in_array($product['idProducto'], $userFavorites ?? []) ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>" 
                                        data-product-id="<?= $product['idProducto'] ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><a href="#producto-<?= $product['idProducto'] ?>"><?= htmlspecialchars($product['nombre']) ?></a></h3>
                                <p class="product-price">$<?= number_format($product['precio'], 0, ',', '.') ?></p>
                                <div class="product-actions">
                                    <button class="btn-buy-now" aria-label="Comprar ahora">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="9" cy="21" r="1"></circle>
                                            <circle cx="20" cy="21" r="1"></circle>
                                            <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                        </svg>
                                    </button>
                                    <button class="btn-add-cart" aria-label="Añadir al carrito">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14M5 12h14"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="products-section">
                <h2 id="pdestacados" class="section-title">Descubre nuestros productos destacados</h2>
                
                <div class="products-container">
                    <?php foreach (array_slice($products, 0, 4) as $product): ?>
                    <article class="product-card" data-product-id="<?= $product['idProducto'] ?>">
                        <div class="product-card-inner">
                            <div class="product-image">
                                <img src="img/main/IBUPROFENO-CAP-BLAN-800MG-CJAX30.png" alt="<?= htmlspecialchars($product['nombre']) ?>">
                                <button class="favorite-btn <?= in_array($product['idProducto'], $userFavorites ?? []) ? 'active' : '' ?>" 
                                        aria-label="<?= in_array($product['idProducto'], $userFavorites ?? []) ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>" 
                                        data-product-id="<?= $product['idProducto'] ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><a href="#producto-<?= $product['idProducto'] ?>"><?= htmlspecialchars($product['nombre']) ?></a></h3>
                                <p class="product-price">$<?= number_format($product['precio'], 0, ',', '.') ?></p>
                                <div class="product-actions">
                                    <button class="btn-buy-now" aria-label="Comprar ahora">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="9" cy="21" r="1"></circle>
                                            <circle cx="20" cy="21" r="1"></circle>
                                            <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                        </svg>
                                    </button>
                                    <button class="btn-add-cart" aria-label="Añadir al carrito">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14M5 12h14"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="finalfield">
                <div class="finalMesagge">
                    <div class="maleImage">
                        <img src="img/main/maleDoctorCube.png">
                    </div>
                    <div class="textFinalMessage">
                        <div class="contactUs">
                            <p>Si estas interesado(a) en conectar con nosotros</p>
                        </div>
                        <div class="contactUs">
                            <p>¡Contáctanos!</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer>
            <div class="FooterTextLinks">
                <div class="contactField">
                    <div class="textoA1">
                        <p>Contáctanos</p>
                    </div>
                    <div class="textoA2">
                        <p>bluedigitalpharms@gmail.com</p>
                    </div>
                    <p class="footerCopyright">Copyright &copy; 2025 Website. Todos los derechos reservados.</p>                    
                </div>
                <div class="linkFooterFields">
                    <div class="TextualButtons">
                        <a href="#conecta" class="Link" target="_blank">Conecta con nosotros</a>
                    </div>
                    <div class="TextualButtons">
                        <a href="#terminos" class="Link" target="_blank">Términos y condiciones</a>
                    </div>
                    <div class="TextualButtons">
                        <a href="#ayuda" class="Link">Ayuda</a> 
                    </div>
                </div>
                <div class="socialMediaField">
                    <div class="textoA2">
                        <p>¡Visita nuestra redes sociales!</p>
                    </div>
                    <div class="socialMediaContainer">
                        <div class="socialMediaButtons">
                            <a href="" title="Facebook" target="_blank">
                                <img src="img/footer/facebookNegro.png" alt="Facebook de Blue">
                            </a>
                        </div>
                        <div class="socialMediaButtons">
                            <a href="https://x.com/BlueDPharms" title="Twitter" target="_blank">
                                <img src="img/footer/xNegro.png" alt="Twitter de Blue">
                            </a>
                        </div>
                        <div class="socialMediaButtons">
                            <a href="https://www.instagram.com/blue_pharms/" title="Instagram" target="_blank">
                                <img src="img/footer/instagramNegro.png" alt="Instagram de Blue">
                            </a>
                        </div>
                        <div class="socialMediaButtons">
                            <a href="https://www.linkedin.com/in/blue-farmacout-683b32367/" title="Linkedin" target="_blank">
                                <img src="img/footer/linkedinNegro.png" alt="Linkedin de Blue">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        // Notification System
        document.addEventListener("DOMContentLoaded", () => {
            if (!document.getElementById("notification-container")) {
                const container = document.createElement("div")
                container.id = "notification-container"
                document.body.appendChild(container)
            }
        })

        function showNotification(options = {}) {
            const config = {
                title: options.title || "",
                message: options.message || "",
                type: options.type || "info",
                duration: options.duration !== undefined ? options.duration : 5000,
                dismissible: options.dismissible !== undefined ? options.dismissible : true,
                onClose: options.onClose || null,
                onAction: options.onAction || null,
                actionText: options.actionText || "Aceptar",
            }

            const container = document.getElementById("notification-container")
            const notification = document.createElement("div")
            notification.className = `notification notification-${config.type}`
            const notificationId = "notification-" + Date.now()
            notification.id = notificationId

            const icons = {
                success: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14 9 11"></polyline></svg>',
                error: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
                warning: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                info: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
            }

            notification.innerHTML = `
                <div class="notification-icon">
                    ${icons[config.type] || icons.info}
                </div>
                <div class="notification-content">
                    ${config.title ? `<h3 class="notification-title">${config.title}</h3>` : ""}
                    <p class="notification-message">${config.message}</p>
                    ${config.onAction ? `<button class="notification-action">${config.actionText}</button>` : ""}
                </div>
                ${config.dismissible ? '<button class="notification-close">&times;</button>' : ""}
            `

            container.appendChild(notification)

            setTimeout(() => {
                notification.classList.add("show")
            }, 10)

            let timeout
            if (config.duration > 0) {
                timeout = setTimeout(() => {
                    closeNotification(notificationId, config.onClose)
                }, config.duration)
            }

            if (config.dismissible) {
                const closeBtn = notification.querySelector(".notification-close")
                closeBtn.addEventListener("click", () => {
                    clearTimeout(timeout)
                    closeNotification(notificationId, config.onClose)
                })
            }

            if (config.onAction) {
                const actionBtn = notification.querySelector(".notification-action")
                actionBtn.addEventListener("click", () => {
                    config.onAction()
                    clearTimeout(timeout)
                    closeNotification(notificationId, config.onClose)
                })
            }

            return notificationId
        }

        function closeNotification(id, callback = null) {
            const notification = document.getElementById(id)
            if (!notification) return

            notification.classList.remove("show")
            notification.classList.add("hide")

            setTimeout(() => {
                notification.remove()
                if (callback && typeof callback === "function") {
                    callback()
                }
            }, 300)
        }

        function showSuccessNotification(message, title = "Éxito") {
            return showNotification({ title, message, type: "success" })
        }

        function showErrorNotification(message, title = "Error") {
            return showNotification({ title, message, type: "error", duration: 0 })
        }

        function showInfoNotification(message, title = "Información") {
            return showNotification({ title, message, type: "info" })
        }

        function showWarningNotification(message, title = "Advertencia") {
            return showNotification({ title, message, type: "warning", duration: 8000 })
        }

        // Search System
        document.addEventListener("DOMContentLoaded", () => {
            createSearchPanel()
            setupSearchEvents()

            let searchTimeout = null
            let lastSearchTerm = ""

            function createSearchPanel() {
                const searchPanel = document.createElement("div")
                searchPanel.id = "search-panel"
                searchPanel.className = "search-panel"
                searchPanel.innerHTML = `
                    <div class="search-container">
                        <div class="search-header">
                            <input type="text" id="search-input" placeholder="¿Qué estás buscando?" autocomplete="off">
                            <button id="search-close" aria-label="Cerrar búsqueda">&times;</button>
                        </div>
                        <div class="search-results" id="search-results">
                            <div class="search-message">Escribe para buscar productos</div>
                        </div>
                    </div>
                `

                document.body.appendChild(searchPanel)
            }

            function setupSearchEvents() {
                const searchButtons = document.querySelectorAll('.CircularButtons a[title="Buscar"]')
                searchButtons.forEach((button) => {
                    button.addEventListener("click", (e) => {
                        e.preventDefault()
                        toggleSearchPanel()
                    })
                })

                const closeButton = document.getElementById("search-close")
                if (closeButton) {
                    closeButton.addEventListener("click", () => {
                        toggleSearchPanel(false)
                    })
                }

                const searchInput = document.getElementById("search-input")
                if (searchInput) {
                    searchInput.addEventListener("input", handleSearchInput)
                    searchInput.addEventListener("keydown", (e) => {
                        if (e.key === "Escape") {
                            toggleSearchPanel(false)
                        }
                    })
                }

                document.addEventListener("click", (e) => {
                    const searchPanel = document.getElementById("search-panel")
                    const searchButtons = document.querySelectorAll('.CircularButtons a[title="Buscar"]')

                    let clickedOnSearchButton = false
                    searchButtons.forEach((button) => {
                        if (button.contains(e.target)) {
                            clickedOnSearchButton = true
                        }
                    })

                    if (
                        searchPanel &&
                        searchPanel.classList.contains("active") &&
                        !searchPanel.contains(e.target) &&
                        !clickedOnSearchButton
                    ) {
                        toggleSearchPanel(false)
                    }
                })
            }

            function handleSearchInput(e) {
                const searchTerm = e.target.value.trim()

                if (searchTimeout) {
                    clearTimeout(searchTimeout)
                }

                if (searchTerm === lastSearchTerm || searchTerm.length < 2) {
                    if (searchTerm.length === 0) {
                        showSearchMessage("Escribe para buscar productos")
                    } else if (searchTerm.length === 1) {
                        showSearchMessage("Escribe al menos 2 caracteres para buscar")
                    }
                    return
                }

                showSearchMessage("Buscando...", true)

                searchTimeout = setTimeout(() => {
                    performSearch(searchTerm)
                    lastSearchTerm = searchTerm
                }, 300)
            }

            async function performSearch(searchTerm) {
                try {
                    const response = await fetch(`backend/php/search-products.php?q=${encodeURIComponent(searchTerm)}`)
                    const result = await response.json()

                    if (result.success) {
                        displaySearchResults(result.data.products, searchTerm)
                    } else {
                        throw new Error(result.message || "Error en la búsqueda")
                    }
                } catch (error) {
                    console.error("Error en la búsqueda:", error)
                    showSearchMessage("Error al buscar productos. Intenta de nuevo.")
                }
            }

            function displaySearchResults(products, searchTerm) {
                const resultsContainer = document.getElementById("search-results")

                if (!products || products.length === 0) {
                    showSearchMessage(`No se encontraron productos para "${searchTerm}"`)
                    return
                }

                let html = `
                    <div class="search-results-header">
                        <span>${products.length} resultado${products.length !== 1 ? "s" : ""} para "${searchTerm}"</span>
                    </div>
                    <div class="search-results-grid">
                `

                products.forEach((product) => {
                    html += `
                        <a href="#producto-${product.idProducto}" class="search-result-item">
                            <div class="search-result-image">
                                <img src="${product.imagen || "img/main/placeholder-product.png"}" alt="${product.nombre}">
                            </div>
                            <div class="search-result-info">
                                <h3>${highlightSearchTerm(product.nombre, searchTerm)}</h3>
                                <p class="search-result-price">$${formatPrice(product.precio)}</p>
                            </div>
                        </a>
                    `
                })

                html += `
                    </div>
                    <div class="search-results-footer">
                        <a href="#busqueda?q=${encodeURIComponent(searchTerm)}" class="search-view-all">Ver todos los resultados</a>
                    </div>
                `

                resultsContainer.innerHTML = html
            }

            function showSearchMessage(message, loading = false) {
                const resultsContainer = document.getElementById("search-results")
                resultsContainer.innerHTML = `
                    <div class="search-message ${loading ? "loading" : ""}">
                        ${message}
                    </div>
                `
            }

            function highlightSearchTerm(text, term) {
                if (!term) return text
                const regex = new RegExp(`(${escapeRegExp(term)})`, "gi")
                return text.replace(regex, "<mark>$1</mark>")
            }

            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
            }

            function formatPrice(price) {
                return new Intl.NumberFormat("es-CO", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(price)
            }

            function toggleSearchPanel(show = true) {
                const searchPanel = document.getElementById("search-panel")
                const searchInput = document.getElementById("search-input")

                if (show) {
                    searchPanel.classList.add("active")
                    setTimeout(() => {
                        searchInput.focus()
                    }, 300)
                } else {
                    searchPanel.classList.remove("active")
                    searchInput.value = ""
                    lastSearchTerm = ""
                }
            }

            window.toggleSearchPanel = toggleSearchPanel

// Make search system globally available
window.createGlobalSearchSystem = function() {
    if (document.getElementById("search-panel")) return; // Already exists
    
    createSearchPanel()
    setupSearchEvents()
}

// Auto-initialize if not already done
if (!document.getElementById("search-panel")) {
    window.createGlobalSearchSystem()
}
        })

        // Responsive Header
        document.addEventListener("DOMContentLoaded", () => {
            const header = document.querySelector(".HeaderSite");
            const nav = header ? header.querySelector(".Nav") : null;

            if (!header || !nav) {
                console.warn("Header o Nav no encontrados. El menú responsivo no se inicializará.");
                return;
            }

            setupMobileHeaderElements(header, nav);
            setupHeaderEvents(nav);

            function setupMobileHeaderElements(headerEl, navEl) {
                const menuToggle = document.createElement("button");
                menuToggle.className = "menu-toggle";
                menuToggle.setAttribute("aria-label", "Abrir menú");
                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.innerHTML = `
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                `;

                const overlay = document.createElement("div");
                overlay.className = "mobile-menu-overlay";

                headerEl.insertBefore(menuToggle, headerEl.firstChild);
                document.body.appendChild(overlay);
            }

            function setupHeaderEvents(navEl) {
                const menuToggle = document.querySelector(".menu-toggle");
                const overlay = document.querySelector(".mobile-menu-overlay");

                if (menuToggle && overlay) {
                    menuToggle.addEventListener("click", () => {
                        const isExpanded = menuToggle.getAttribute("aria-expanded") === "true" || false;
                        toggleMobileMenu(!isExpanded);
                    });

                    overlay.addEventListener("click", () => {
                        toggleMobileMenu(false);
                    });

                    const navLinks = navEl.querySelectorAll("a");
                    navLinks.forEach((link) => {
                        link.addEventListener("click", () => {
                            toggleMobileMenu(false);
                        });
                    });

                    window.addEventListener("resize", () => {
                        if (window.innerWidth > 768 && navEl.classList.contains("active")) {
                            toggleMobileMenu(false);
                        }
                    });
                }
            }

            function toggleMobileMenu(show) {
                const menuToggle = document.querySelector(".menu-toggle");
                const nav = document.querySelector(".Nav");
                const overlay = document.querySelector(".mobile-menu-overlay");

                if (menuToggle && nav && overlay) {
                    menuToggle.setAttribute("aria-expanded", show.toString());
                    if (show) {
                        menuToggle.classList.add("active");
                        nav.classList.add("active");
                        overlay.classList.add("active");
                        document.body.style.overflow = "hidden";
                    } else {
                        menuToggle.classList.remove("active");
                        nav.classList.remove("active");
                        overlay.classList.remove("active");
                        document.body.style.overflow = "";
                    }
                }
            }
        })

        // Main functionality
        document.addEventListener("DOMContentLoaded", () => {
            async function checkAuthStatus() {
                try {
                    const response = await fetch("backend/php/check-auth.php")
                    const result = await response.json()
                    return result.success
                } catch (error) {
                    return false
                }
            }

            async function addToCart(productId, quantity = 1) {
                try {
                    const response = await fetch("backend/php/add-to-cart.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({ productId, quantity }),
                    })

                    const result = await response.json()

                    if (result.success) {
                        updateCartCounter(result.data.cart_count)
                        return true
                    } else {
                        throw new Error(result.message)
                    }
                } catch (error) {
                    console.error("Error adding to cart:", error)
                    showErrorNotification(error.message || "Error añadiendo al carrito")
                    return false
                }
            }

            async function toggleFavorite(productId, button) {
    try {
        const response = await fetch("backend/php/toggle-favorite.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ productId }),
        });

        const result = await response.json();

        if (result.success) {
            const isFavorite = result.data.is_favorite;
            const productName = result.data.product_name || "Producto";
            
            // Actualizar todos los botones para este producto en la página
            const allButtons = document.querySelectorAll(`.favorite-btn[data-product-id="${productId}"]`);
            allButtons.forEach(btn => {
                if (isFavorite) {
                    btn.classList.add("active");
                    btn.style.color = "#fff";
                    btn.style.backgroundColor = "#e74c3c";
                    btn.style.borderColor = "#e74c3c";
                    btn.setAttribute("aria-label", "Quitar de favoritos");
                } else {
                    btn.classList.remove("active");
                    btn.style.color = "#666";
                    btn.style.backgroundColor = "rgba(255, 255, 255, 0.9)";
                    btn.style.borderColor = "#ddd";
                    btn.setAttribute("aria-label", "Agregar a favoritos");
                }
            });
            
            if (isFavorite) {
                showSuccessNotification(`${productName} añadido a favoritos`);
            } else {
                showInfoNotification(`${productName} removido de favoritos`);
            }
            
            return isFavorite;
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error("Error toggling favorite:", error);
        showErrorNotification(error.message || "Error gestionando favoritos");
        return false;
    }
}

            function updateCartCounter(count) {
                const cartButtons = document.querySelectorAll('a[title="Carrito de Compra"]')
                cartButtons.forEach((button) => {
                    let counter = button.querySelector(".cart-counter")
                    if (!counter) {
                        counter = document.createElement("span")
                        counter.className = "cart-counter"
                        counter.style.cssText = `
                            position: absolute;
                            top: -5px;
                            right: -5px;
                            background: #e74c3c;
                            color: white;
                            border-radius: 50%;
                            width: 20px;
                            height: 20px;
                            font-size: 12px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                        `
                        button.style.position = "relative"
                        button.appendChild(counter)
                    }
                    counter.textContent = count > 0 ? count : ""
                    counter.style.display = count > 0 ? "flex" : "none"
                })
            }

            // Add to cart button listeners
            document.querySelectorAll(".btn-add-cart").forEach((button) => {
                button.addEventListener("click", async (event) => {
                    event.preventDefault()

                    const productCard = event.target.closest(".product-card")
                    const productId = productCard ? productCard.dataset.productId : null
                    const productName = productCard ? productCard.querySelector(".product-name a").textContent : "Producto"

                    if (!productId) {
                        showErrorNotification("Error: ID del producto no encontrado")
                        return
                    }

                    console.log(`🛒 Añadiendo producto ${productId} (${productName}) al carrito.`)

                    button.style.transform = "scale(0.95)"
                    setTimeout(() => {
                        button.style.transform = "scale(1)"
                    }, 150)

                    const success = await addToCart(parseInt(productId))
                    if (success) {
                        showSuccessNotification(`${productName} añadido al carrito`)
                    }
                })
            })

            // Favorite button listeners
            document.querySelectorAll(".favorite-btn").forEach((button) => {
                button.addEventListener("click", async (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const productCard = event.target.closest(".product-card");
                    const productId = button.dataset.productId || (productCard ? productCard.dataset.productId : null);
                    const productName = productCard ? productCard.querySelector(".product-name a").textContent : "Producto";

                    if (!productId) {
                        showErrorNotification("Error: ID del producto no encontrado");
                        return;
                    }

                    button.style.transform = "scale(1.2)";
                    setTimeout(() => {
                        button.style.transform = "scale(1)";
                    }, 200);

                    const isAuthenticated = await checkAuthStatus();
                    if (!isAuthenticated) {
                        showLoginPrompt();
                        return;
                    }

                    await toggleFavorite(parseInt(productId), button);
                });
            })

            // Auth check
            async function initAuthCheck() {
                try {
                    const response = await fetch("backend/php/check-auth.php")
                    const result = await response.json()

                    if (result.success) {
                        handleAuthenticatedUser(result.data.user)
                    } else {
                        handleUnauthenticatedUser()
                    }
                } catch (error) {
                    console.log("Auth check failed:", error)
                    handleUnauthenticatedUser()
                }
            }

            function handleAuthenticatedUser(user) {
    // No modificar el header, mantener solo el ícono
    if (!sessionStorage.getItem("welcomeShown")) {
        showSuccessNotification(`¡Bienvenido de vuelta, ${user.name}!`, "Sesión iniciada")
        sessionStorage.setItem("welcomeShown", "true")
    }
}

            function handleUnauthenticatedUser() {
                const protectedButtons = document.querySelectorAll(".btn-add-cart, .favorite-btn")
                protectedButtons.forEach((button) => {
                    button.addEventListener("click", (e) => {
                        e.preventDefault()
                        showLoginPrompt()
                    })
                })
            }

            function showLoginPrompt() {
                showInfoNotification("Debes iniciar sesión para realizar esta acción", "Autenticación requerida")
                setTimeout(() => {
                    window.location.href = "login.php"
                }, 2000)
            }

            initAuthCheck()
        })

// Función para verificar el estado de favoritos al cargar la página
async function checkFavoriteStatus(productId) {
    if (!productId) return false;
    
    try {
        const response = await fetch(`backend/php/check-favorites.php?productId=${productId}`);
        const result = await response.json();
        
        if (result.success) {
            return result.data.is_favorite;
        }
        return false;
    } catch (error) {
        console.error("Error checking favorite status:", error);
        return false;
    }
}

// Actualizar visualmente los botones de favoritos al cargar la página
document.addEventListener("DOMContentLoaded", async () => {
    const isAuthenticated = await checkAuthStatus();
    if (!isAuthenticated) return;
    
    const favoriteButtons = document.querySelectorAll(".favorite-btn");
    favoriteButtons.forEach(async (button) => {
        const productId = button.dataset.productId;
        if (!productId) return;
        
        const isFavorite = await checkFavoriteStatus(productId);
        if (isFavorite) {
            button.classList.add("active");
            button.style.color = "#fff";
            button.style.backgroundColor = "#e74c3c";
            button.style.borderColor = "#e74c3c";
            button.setAttribute("aria-label", "Quitar de favoritos");
        } else {
            button.classList.remove("active");
            button.style.color = "#666";
            button.style.backgroundColor = "rgba(255, 255, 255, 0.9)";
            button.style.borderColor = "#ddd";
            button.setAttribute("aria-label", "Agregar a favoritos");
        }
    });
});

        function showDemoNotification() {
            showSuccessNotification(
                "¡Gracias por tu interés en Blue Pharmacy!",
                "Mensaje recibido"
            );
        }

// Reemplazar la función buyNow existente con esta nueva implementación
window.buyNow = function(productId) {
    console.log("Buy Now clicked for product ID:", productId);
    // Redirigir directamente a la página de compra rápida
    window.location.href = `quick-checkout.php?product=${productId}`;
}
    </script>
</body>
</html>
