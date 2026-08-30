# pdf-generation Specification (delta)

---
status: proposed
---

## Purpose

Extend the Twig sandbox security policy (REQ-PDF-03 / REQ-PDF-07 family) with
exactly three whitelisted, side-effect-bounded template functions — `chart`,
`data_table`, `nc_image` — introduced by the `template-charts` change. Every
existing sandbox guarantee (function/filter/tag whitelists, no object
methods or properties, autoescape) is preserved; this delta only ADDs.

## ADDED Requirements

### Requirement: The Twig sandbox whitelists exactly three visual-content functions (REQ-DDTCH-005)

The sandboxed Twig environment MUST extend its allowed-functions whitelist
with exactly `chart`, `data_table`, and `nc_image`, and with nothing else: no
new tags, no new filters, and no relaxation of the existing policy (object
methods and properties remain uncallable; non-whitelisted functions remain
refused with a sandbox security error). The three functions MUST be pure with
respect to the instance — no writes, no network I/O; `nc_image` performs
read-only file access strictly as the generating user (REQ-DDTCH-006). Their
return values are app-generated markup emitted as safe HTML while all
data-derived content inside that markup MUST be escaped by the renderers.
The existing sandbox refusal tests MUST be extended (not replaced) to pin the
new whitelist as exact.

#### Scenario: New functions are callable, everything else still refused

- GIVEN the sandboxed renderer after this change
- WHEN a template calls `chart`, `data_table`, and `nc_image`, and another template calls a non-whitelisted function such as `dump` or attempts an object method call
- THEN the three whitelisted functions execute
- AND the non-whitelisted function and the method access are refused with a Twig sandbox security error, exactly as before this change
- @e2e exclude sandbox-policy pin with no UI surface — covered by PHPUnit (tests/unit/Service/TemplateRendererTest.php::testWhitelistIsExact)

#### Scenario: Whitelisted functions cannot write or reach the network

- GIVEN the implementations registered for the three functions
- WHEN their execution paths are exercised in the unit suite
- THEN no code path performs a write operation or an outbound network call
- AND `nc_image` reads only through the generating user's folder
- @e2e exclude purity/static-contract pin; covered by PHPUnit (tests/unit/Service/TemplateRendererTest.php::testVisualFunctionsAreSideEffectBounded)
