# Siroko — Carrito & Checkout

Prueba técnica de Siroko, **Opción A (Carrito & Checkout)**. Gestión de un carrito de compra
(añadir/actualizar/eliminar + obtener contenido) y un checkout que genera una **orden persistente**.

- **Stack:** PHP 8.3, Symfony 7, MySQL 8 (Doctrine ORM), PHPUnit, Docker.
- **Arquitectura:** Hexagonal + DDD + CQRS.
- **Dinero:** siempre entero en **céntimos** (nunca `float`).

---

## Arquitectura en una frase

La dependencia apunta hacia dentro: **Infraestructura → Aplicación → Dominio**, y el **dominio es PHP puro** (no conoce Symfony ni Doctrine).

```
src/
  Shared/Domain/            Money, Quantity, identificadores (value objects)
  Cart/
    Domain/                 Cart (agregado), CartItem, PriceList, StockLevels, puertos
    Application/            AddItem, UpdateItem, RemoveItem, GetCart (casos de uso CQRS)
    Infrastructure/         Doctrine (records + repos), catálogo, controllers HTTP
  Order/
    Domain/                 Order (agregado), OrderLine, OrderStatus, puertos
    Application/            Checkout
    Infrastructure/         Doctrine, generador de id (symfony/uid), pago fake, controller
```

El mapeo ORM vive en `*/Infrastructure/Persistence` como **records planos** separados; el dominio
no lleva ni un atributo de Doctrine (ver `ai/DECISIONS.md` D-009).

---

## Arranque rápido (`docker-compose`)

```bash
docker compose up -d --build

# Instalar dependencias (primera vez)
docker compose run --rm --no-deps php composer install

# Esquema de base de datos
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Sembrar el catálogo (1000 productos de prueba)
docker compose exec php php bin/console app:seed-catalog --count=1000
```

La API queda en **http://localhost:8080**. Comprobación: `curl http://localhost:8080/health`.

### Ejemplo de flujo completo

```bash
curl -X POST   http://localhost:8080/cart/cart-1/items -H 'Content-Type: application/json' -d '{"productId":"SKU-0001","quantity":2}'
curl -X POST   http://localhost:8080/cart/cart-1/items -H 'Content-Type: application/json' -d '{"productId":"SKU-0002","quantity":3}'
curl           http://localhost:8080/cart/cart-1
curl -X PATCH  http://localhost:8080/cart/cart-1/items/SKU-0001 -H 'Content-Type: application/json' -d '{"quantity":5}'
curl -X DELETE http://localhost:8080/cart/cart-1/items/SKU-0002
curl -X POST   http://localhost:8080/cart/cart-1/checkout
```

---

## API

| Método | Ruta | Acción | Éxito |
|---|---|---|---|
| `POST` | `/cart/{cartId}/items` | Añadir producto (crea el carrito si no existe) | 204 |
| `PATCH` | `/cart/{cartId}/items/{productId}` | Actualizar cantidad (absoluta) | 204 |
| `DELETE` | `/cart/{cartId}/items/{productId}` | Eliminar línea | 204 |
| `GET` | `/cart/{cartId}` | Obtener contenido (con precios vigentes y total) | 200 |
| `POST` | `/cart/{cartId}/checkout` | Checkout → orden persistente | 201 (PAID) / 402 (FAILED) |

Errores de dominio mapeados a HTTP por un listener central: **404** (no encontrado),
**400** (entrada inválida), **409** (carrito vacío / stock insuficiente).

Especificación completa: [`openapi.yaml`](openapi.yaml).

---

## Tests

La capa de dominio y de aplicación se testean **sin framework** (PHP puro, fakes in-memory de los
puertos). Se verifican **en PHP 8.3** (la versión de entrega), vía Docker:

```bash
docker run --rm -v "$PWD":/app -w /app php:8.3-cli php vendor/bin/phpunit
```

> 112 tests / 170 aserciones en verde. Cubren invariantes del dominio y todos los *sad paths*
> de los casos de uso (carrito vacío, stock insuficiente, pago rechazado, cantidad < 1,
> transición de estado ilegal, etc.).

---

## Performance

Medida con datos (no "es rápido" sin número): la consulta de precios/stock es **batch**
(`WHERE id IN (...)`), de modo que `GET /cart` se mantiene **plano (~30–45 ms) de 10 a 100 líneas**.
Detalle y reproducción en [`ai/PERFORMANCE.md`](ai/PERFORMANCE.md).

---

## Documentación de IA y decisiones

La carpeta [`/ai`](ai) contiene:

- [`PLAN.md`](ai/PLAN.md) — especificación funcional y de diseño (el QUÉ).
- [`CLAUDE.md`](ai/CLAUDE.md) — reglas de cómo trabajar (el CÓMO).
- [`DECISIONS.md`](ai/DECISIONS.md) — registro de decisiones (qué se decidió y por qué).
- [`PERFORMANCE.md`](ai/PERFORMANCE.md) — medición de rendimiento.
