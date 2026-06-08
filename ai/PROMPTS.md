# PROMPTS — Siroko Carrito & Checkout

> Prompts clave que guiaron a la IA durante la construcción, en orden. No es el log literal
> completo, sino las instrucciones que marcaron el rumbo y las decisiones. El detalle del
> "por qué" de cada decisión está en `DECISIONS.md`; el "qué" en `PLAN.md`; el "cómo" en `CLAUDE.md`.

## 0. Encuadre

> "Lee los ficheros CLAUDE.md y ai/PLAN.md. Son las reglas del proyecto y la especificación de
> diseño. No escribas código todavía. Resúmeme: qué arquitectura vamos a seguir, cuál es la regla
> de oro del dominio, y en qué orden vamos a construir las piezas."

Estableció el contrato antes de tocar código: Hexagonal + DDD + CQRS, dominio PHP puro, y el orden
`Money → Quantity → identificadores → CartItem → Cart → OrderLine/Order → puertos → casos de uso → adaptadores`.

## 1. Dominio (pieza a pieza, con tests)

- > "Por ahora es una sola moneda." → D-002 (Money modela la moneda igual, rechaza mezclas).
- > "Empieza con Money."
- > "Los test han pasado en PHP 8.5, pero cuando llegue el momento comprueba que pasan en la
>   versión correcta, PHP 8.3. No podemos dejar nada al azar." → D-001 (suite siempre en `php:8.3-cli`).
- > "Crea ai/DECISIONS.md y añade esta entrada... así como la decisión de multi-moneda."
- > "Veo que InvalidQuantity extiende InvalidArgumentException, pero en Money las de dominio
>   extienden DomainException. Unifícalo... por coherencia." → D-003.
- > "Sigamos con los identificadores / CartItem."
- > "Antes de aprobar el test, muéstrame la clase Cart primero." (revisión humana antes de fijar tests).
- > "Sigamos con OrderLine / Order."

## 2. Puertos

- > "A la primera pregunta siempre Excepción, tenemos que controlar siempre qué devolvemos.
>   Devuelve el listado de precios completo." → get() lanza, nunca null; `pricesFor(): PriceList`.
- > "Cuidado con la firma de pricesFor. No quiero que devuelva el catálogo entero... que reciba
>   los ids del carrito y devuelva un PriceList solo con esos, en una consulta batch."
- > "Añade un método explícito al catálogo para validar existencia en vez de usar pricesFor
>   descartado." → D-008 (`ProductCatalog::exists()`).

## 3. Casos de uso (Aplicación, CQRS)

- > "Empezamos por los comandos de carrito... Comandos de carrito que devuelvan void. Vamos pieza a pieza."
- > "Si el carrito no existe se crea con el cartId dado." → D-004 (AddItem get-or-create).
- > "Muéstrame el AddItemToCartHandler y los dos InMemory que usan los tests." Y a partir de su
>   revisión: método `exists()` explícito (D-008), doble del catálogo mutable, y
>   **repo in-memory que clona en profundidad** para ser fiel a una BD.
- > "Plantéame esas dos decisiones antes de escribir [Checkout]." → D-005 (puerto `OrderIdGenerator`)
>   y D-006 (`Checkout` devuelve `CheckoutResult`; pago rechazado = resultado, no excepción).
- La IA señaló una contradicción del PLAN (persistir al final vs. razón declarada) y se resolvió
  por criterio: persistir la orden en PENDING **antes** de cobrar. → D-007.

## 4. Infraestructura

- > "Plantéame las decisiones de infra primero."
- > "El mapeo de Doctrine sin contaminar el dominio." → D-009 (modelo de persistencia separado +
>   ORM; records planos en Infraestructura, repos que traducen).
- Catálogo en tabla MySQL + fixtures (D-010); listener central de excepciones → HTTP (D-011).
- > "Tengo 2 horas para entregar. Construye toda la infraestructura de una... prioriza que funcione
>   end-to-end con docker-compose. Luego performance medida, OpenAPI y README."

## 5. Trazabilidad

A lo largo de la sesión, instrucciones recurrentes: commits por capa que reflejen las decisiones,
distinguiendo criterio propio de sugerencia de IA, y push a `origin/main`.

---

### Patrón de trabajo observado

1. Decisión de negocio explícita por parte del humano **antes** de codificar lo ambiguo.
2. Una pieza → sus tests → revisión humana → siguiente pieza.
3. Cada decisión no trivial registrada en `DECISIONS.md` con su porqué y la alternativa descartada.
4. La suite verificada siempre en la versión de entrega (PHP 8.3).
