---
title: Editoriale
content:
  items: "@self.children"
  order:
    by: date
    dir: desc

  limit: 20
  pagination: true

pagination: true
route: /blog
menu: Editoriale
visible: true
process:
  markdown: true
  twig: false

taxonomy:
  category:
    - editoriale

---

Analisi e retrospettive sismiche di Quakrs.
