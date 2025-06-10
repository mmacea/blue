-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3307
-- Tiempo de generación: 09-06-2025 a las 23:01:19
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `blue`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `idCarrito` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `fechaAgregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `idCategoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`idCategoria`, `nombre`, `descripcion`) VALUES
(1, 'Medicina', 'Medicamentos y productos farmacéuticos'),
(2, 'Cuidado Personal', 'Productos de higiene y cuidado personal'),
(3, 'Sanidad', 'Productos de sanidad y limpieza'),
(4, 'Cuidado del Bebé', 'Productos especializados para bebés'),
(5, 'Salud Sexual', 'Productos de salud sexual y reproductiva');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccionentrega`
--

CREATE TABLE `direccionentrega` (
  `idDireccion` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `direccionCompleta` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `direccionentrega`
--

INSERT INTO `direccionentrega` (`idDireccion`, `idUsuario`, `direccionCompleta`) VALUES
(1, 1, 'Cl. 27 Cra.32 #87 a sur-13'),
(6, 1, 'oavneñ');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadopedido`
--

CREATE TABLE `estadopedido` (
  `idEstado` int(11) NOT NULL,
  `estado` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estadopedido`
--

INSERT INTO `estadopedido` (`idEstado`, `estado`) VALUES
(5, 'Cancelado'),
(4, 'Entregado'),
(3, 'Enviado'),
(1, 'Pendiente'),
(2, 'Procesando');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `idFavorito` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `fechaAgregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

INSERT INTO `favoritos` (`idFavorito`, `idUsuario`, `idProducto`, `fechaAgregado`) VALUES
(4, 1, 3, '2025-06-02 19:07:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodopago`
--

CREATE TABLE `metodopago` (
  `idMetodoPago` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `detalles` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metodopago`
--

INSERT INTO `metodopago` (`idMetodoPago`, `tipo`, `detalles`) VALUES
(1, 'Contraentrega', 'Pago en efectivo al recibir el pedido'),
(2, 'Tarjeta de Crédito', 'Visa terminada en 1234'),
(3, 'Tarjeta de Débito', 'Mastercard terminada en 5678');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `idNotificacion` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'info',
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`idNotificacion`, `idUsuario`, `titulo`, `mensaje`, `tipo`, `leida`, `fecha`) VALUES
(1, 1, '¡Bienvenido a Blue Pharmacy!', 'Tu cuenta ha sido creada exitosamente. ¡Comienza a explorar nuestros productos!', 'success', 0, '2025-06-02 00:36:31'),
(2, 1, 'Bienvenido de vuelta', 'Has iniciado sesión exitosamente en Blue Pharmacy', 'success', 0, '2025-06-02 00:36:43'),
(3, 1, 'Bienvenido de vuelta', 'Has iniciado sesión exitosamente en Blue Pharmacy', 'success', 0, '2025-06-02 00:46:58'),
(4, 1, 'Pedido creado exitosamente', 'Tu pedido #1 ha sido creado y está siendo procesado', 'success', 0, '2025-06-02 02:01:09'),
(5, 1, 'Pedido creado exitosamente', 'Tu pedido #2 ha sido creado y está siendo procesado', 'success', 0, '2025-06-02 02:11:27'),
(13, 1, 'Pedido creado exitosamente', 'Tu pedido #10 ha sido creado y está siendo procesado', 'success', 0, '2025-06-02 21:05:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidoproducto`
--

CREATE TABLE `pedidoproducto` (
  `idPedido` int(11) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precioUnitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidoproducto`
--

INSERT INTO `pedidoproducto` (`idPedido`, `idProducto`, `cantidad`, `precioUnitario`) VALUES
(1, 6, 1, 22000.00),
(2, 1, 1, 8490.00),
(2, 2, 1, 10990.00),
(2, 3, 1, 17990.00),
(10, 2, 2, 10990.00),
(10, 6, 1, 22000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `idPedido` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idEstado` int(11) NOT NULL,
  `idDireccion` int(11) NOT NULL,
  `idMetodoPago` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `montoTotal` decimal(10,2) NOT NULL,
  `detalles` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`idPedido`, `idUsuario`, `idEstado`, `idDireccion`, `idMetodoPago`, `fecha`, `montoTotal`, `detalles`) VALUES
(1, 1, 1, 1, 1, '2025-06-02 02:01:09', 22000.00, ''),
(2, 1, 1, 1, 1, '2025-06-02 02:11:27', 37470.00, ''),
(10, 1, 1, 6, 1, '2025-06-02 21:05:42', 43980.00, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `idProducto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(225) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `idCategoria` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`idProducto`, `nombre`, `descripcion`, `precio`, `stock`, `idCategoria`) VALUES
(1, 'Alcohol JGB 700ml', 'Alcohol antiséptico para uso externo, 700ml', 8490.00, 49, 3),
(2, 'Ibuprofeno MK TQ 800mg 30 cap', 'Antiinflamatorio y analgésico, 800mg, caja con 30 cápsulas', 10990.00, 27, 1),
(3, 'Acetaminofén Forte MK TQ 500mg 16 tab', 'Analgésico y antipirético, 500mg, caja con 16 tabletas', 17990.00, 24, 1),
(4, 'Preservativos Duo x3', 'Preservativos de látex natural, paquete de 3 unidades', 8900.00, 100, 5),
(5, 'Shampoo Anticaspa H&S 400ml', 'Shampoo especializado contra la caspa, 400ml', 15500.00, 40, 2),
(6, 'Crema Hidratante Facial Cerave 50ml', 'Crema hidratante para rostro, apto para piel seca 50ml', 22000.00, 33, 2),
(7, 'Pañales Bebé Winny Talla M x30', 'Pañales desechables Winny talla M, paquete de 30 unidades', 28500.00, 60, 4),
(8, 'Toallitas Húmedas Bebé Huggies x80', 'Toallitas húmedas para bebé Huggies, paquete de 80 unidades', 12000.00, 45, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `idProducto` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `categoria` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fechaCreacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`idProducto`, `nombre`, `descripcion`, `precio`, `stock`, `categoria`, `imagen`, `estado`, `fechaCreacion`) VALUES
(1, 'Paracetamol 500mg', 'Analgésico y antipirético para el alivio del dolor y la fiebre', 15.50, 0, 'Medicamentos', '/placeholder.svg?height=200&width=200', 'activo', '2025-06-09 20:57:16'),
(2, 'Ibuprofeno 400mg', 'Antiinflamatorio no esteroideo para dolor e inflamación', 22.00, 0, 'Medicamentos', '/placeholder.svg?height=200&width=200', 'activo', '2025-06-09 20:57:16'),
(3, 'Vitamina C 1000mg', 'Suplemento vitamínico para fortalecer el sistema inmunológico', 35.00, 0, 'Vitaminas', '/placeholder.svg?height=200&width=200', 'activo', '2025-06-09 20:57:16'),
(4, 'Protector Solar SPF 50', 'Protección solar de amplio espectro', 45.00, 0, 'Cuidado Personal', '/placeholder.svg?height=200&width=200', 'activo', '2025-06-09 20:57:16'),
(5, 'Termómetro Digital', 'Termómetro digital de lectura rápida', 28.00, 0, 'Equipos Médicos', '/placeholder.svg?height=200&width=200', 'activo', '2025-06-09 20:57:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `idSesion` varchar(255) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `fechaCreacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fechaExpiracion` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`idSesion`, `idUsuario`, `fechaCreacion`, `fechaExpiracion`, `activa`) VALUES
('1056f9246de5cf7a139897788888a4c7942c8a2cec22b24c912c3be49976821f', 1, '2025-06-02 00:36:43', '2025-07-02 07:36:43', 0),
('2682b22492bf630d26fc3aec6f3ec9b39eb4926e4ef18a2eb325ac51fb64dc9a', 1, '2025-06-02 00:46:58', '2025-07-02 07:46:58', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `idUsuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'usuario',
  `estado` varchar(10) NOT NULL DEFAULT 'activo',
  `emailVerificado` tinyint(1) DEFAULT 0,
  `tokenConfirmacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`idUsuario`, `nombre`, `telefono`, `email`, `contrasena`, `rol`, `estado`, `emailVerificado`, `tokenConfirmacion`) VALUES
(1, 'Morcilla 78', '3002645987', 'm.macea02@proton.me', '$2y$10$EP9zqLY5KX70e9k8J7XDe.PiV4rdR0bJaDALoRqo0.ZOIpwtbFmja', 'usuario', 'activo', 0, 'bae208a214ef59ec2ee647d34407ffbe8b860a884f2bc562821a43b1ea67b1ca');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`idCarrito`),
  ADD UNIQUE KEY `unique_user_product` (`idUsuario`,`idProducto`),
  ADD KEY `fk_carrito_usuario_idx` (`idUsuario`),
  ADD KEY `fk_carrito_producto_idx` (`idProducto`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`idCategoria`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `direccionentrega`
--
ALTER TABLE `direccionentrega`
  ADD PRIMARY KEY (`idDireccion`),
  ADD KEY `fk_direccion_usuario_idx` (`idUsuario`);

--
-- Indices de la tabla `estadopedido`
--
ALTER TABLE `estadopedido`
  ADD PRIMARY KEY (`idEstado`),
  ADD UNIQUE KEY `estado` (`estado`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`idFavorito`),
  ADD UNIQUE KEY `unique_user_product_fav` (`idUsuario`,`idProducto`),
  ADD KEY `fk_favoritos_usuario_idx` (`idUsuario`),
  ADD KEY `fk_favoritos_producto_idx` (`idProducto`);

--
-- Indices de la tabla `metodopago`
--
ALTER TABLE `metodopago`
  ADD PRIMARY KEY (`idMetodoPago`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`idNotificacion`),
  ADD KEY `fk_notificaciones_usuario_idx` (`idUsuario`);

--
-- Indices de la tabla `pedidoproducto`
--
ALTER TABLE `pedidoproducto`
  ADD PRIMARY KEY (`idPedido`,`idProducto`),
  ADD KEY `fk_pedido_producto_producto_idx` (`idProducto`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`idPedido`),
  ADD KEY `fk_pedidos_usuario_idx` (`idUsuario`),
  ADD KEY `fk_pedidos_estado_idx` (`idEstado`),
  ADD KEY `fk_pedidos_direccion_idx` (`idDireccion`),
  ADD KEY `fk_pedidos_metodo_pago_idx` (`idMetodoPago`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`idProducto`),
  ADD KEY `fk_producto_categoria_idx` (`idCategoria`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`idProducto`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`idSesion`),
  ADD KEY `fk_sesiones_usuario_idx` (`idUsuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `telefono` (`telefono`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `idCarrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `idCategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `direccionentrega`
--
ALTER TABLE `direccionentrega`
  MODIFY `idDireccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `estadopedido`
--
ALTER TABLE `estadopedido`
  MODIFY `idEstado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `idFavorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `metodopago`
--
ALTER TABLE `metodopago`
  MODIFY `idMetodoPago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `idNotificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `idPedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `idProducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `idProducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `fk_carrito_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_carrito_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `direccionentrega`
--
ALTER TABLE `direccionentrega`
  ADD CONSTRAINT `fk_direccion_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favoritos_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidoproducto`
--
ALTER TABLE `pedidoproducto`
  ADD CONSTRAINT `fk_pedido_producto_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedidos` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_producto_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_direccion` FOREIGN KEY (`idDireccion`) REFERENCES `direccionentrega` (`idDireccion`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_estado` FOREIGN KEY (`idEstado`) REFERENCES `estadopedido` (`idEstado`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_metodo_pago` FOREIGN KEY (`idMetodoPago`) REFERENCES `metodopago` (`idMetodoPago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idCategoria`) REFERENCES `categoria` (`idCategoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
