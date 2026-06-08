# PERFORMANCE — Siroko Carrito & Checkout

> Requisito del PLAN §10: performance **justificada y medida con datos**, no "es rápido" sin número.

## Qué se mide y por qué

Las dos operaciones con coste relevante son:

- **`GET /cart/{cartId}`** (`GetCart`): carga el carrito y resuelve los **precios vigentes** de todas sus líneas.
- **`POST /cart/{cartId}/checkout`** (`Checkout`): carga el carrito, resuelve **precios y stock**, valida, crea la orden y persiste.

El riesgo de rendimiento clásico aquí es el **N+1**: una consulta de precio/stock por cada línea del carrito. Se evita resolviendo **en una única consulta batch** (`WHERE id IN (...)`) todos los productos de las líneas a la vez.

- Código: `DoctrineProductCatalog::fetch()` → `findBy(['id' => $ids])` (1 sola query).
- `GetCartHandler` / `CheckoutHandler` construyen el array de `ProductId` y llaman **una vez** a `pricesFor()` / `stockFor()`.

Por tanto el nº de consultas al catálogo es **constante** (1 para precios, 1 para stock), independientemente del nº de líneas N.

## Resultados (entorno Docker local: PHP 8.3-fpm + nginx + MySQL 8.0)

Catálogo sembrado con 1.000 productos. Tiempo de respuesta HTTP extremo a extremo (`curl %{time_total}`); `GET /cart` es la media de 5 lecturas.

| Líneas en el carrito (N) | `GET /cart` (media) | `POST /checkout` |
|---|---|---|
| 10  | ~45 ms | ~191 ms |
| 50  | ~30 ms | ~168 ms |
| 100 | ~41 ms | ~293 ms |

## Lectura de los datos

- **`GET /cart` se mantiene plano (~30–45 ms) aunque N pase de 10 a 100** (×10 en líneas). Si hubiera N+1, el tiempo crecería de forma aproximadamente lineal con N. No lo hace → confirma que el acceso al catálogo es **batch** (O(1) consultas), que es justo lo que pide §10.
- **`POST /checkout`** crece de forma suave: además de las 2 consultas batch (precio+stock), hace los `INSERT` de la orden y sus N líneas (O(N) escrituras, inevitable: hay que persistir N líneas) y persiste el estado dos veces (PENDING antes de cobrar y el estado final, ver DECISIONS D-007). Aun así se mantiene por debajo de ~300 ms con 100 líneas.

## Cómo reproducir

```bash
docker compose up -d
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console app:seed-catalog --count=1000

# Crear un carrito de N líneas y medir:
for i in $(seq 1 100); do
  curl -s -o /dev/null -X POST http://localhost:8080/cart/bench/items \
    -H 'Content-Type: application/json' \
    -d "{\"productId\":\"$(printf SKU-%04d $i)\",\"quantity\":1}"
done
curl -s -o /dev/null -w "GET /cart: %{time_total}s\n" http://localhost:8080/cart/bench
curl -s -o /dev/null -w "checkout: %{time_total}s\n" -X POST http://localhost:8080/cart/bench/checkout
```

## Mejora futura (si hiciera falta)

Si el catálogo creciera mucho, el índice de PK sobre `products.id` (ya existente) mantiene el `WHERE id IN (...)` eficiente. El siguiente paso natural sería cachear precios (read-through) para `GetCart`, pero no se implementa por no estar pedido y para no sobre-diseñar.
