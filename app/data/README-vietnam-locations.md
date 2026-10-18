# Vietnam location reference data

`vietnam-locations.json` is derived from the Vietnam Provinces Database maintained by Thang Le Quoc:

- Repository: https://github.com/thanglequoc/vietnamese-provinces-database
- Pinned source commit: `8b78ba5118715e1fa81769286724db79346abf52`
- Source hierarchy: `json/simplified_json_generated_data_vn_units.json`
- Source metadata: `json/vn_provinces_metadata.json`
- License: MIT, Copyright (c) 2021 Thang Le Quoc. The license notice and permission are included in `THIRD-PARTY-LICENSES.md`.
- Dataset metadata: version `v5.2.0`, generated `2026-09-20T13:13:17Z`, reflecting `388/NQ-UBTVQH16`.
- Administrative code names and IDs are cross-checked against the official NSO directory/API by the source project.

The compact file retains the source's Vietnamese province/ward labels and underscore `CodeName` slugs. The province `ho_chi_minh` uses the requested `hcm` alias. Unique ward code names (including `ba_dinh` and `cau_giay`) stay unchanged; the 10 code-name collisions (20 wards) within provinces are disambiguated by appending the official ward code, for example `van_lang_02143`. Runtime validation checks the selected province/ward pair.

The source contains 34 provinces and 3,321 wards. Compared with the earlier Open Admin Data snapshot used during initial implementation, 191 ward labels differ on shared codes (including orthographic variants), and province labels differ for `Thanh Hoá` (formerly `Thanh Hóa`) and `Khánh Hoà` (formerly `Khánh Hòa`). One ward code was replaced in `Quảng Ninh` (`06978` → `06970`, both labeled `Ba Chẽ`); all 3,320 other ward codes are shared. Province/ward `CodeName` values match after normalization on shared ward codes.
