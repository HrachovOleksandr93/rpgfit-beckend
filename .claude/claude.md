# 🤖 Backend Project Workflow & Guidelines (Symfony)

## 1. Project Context
This directory (`/rpgfit-backend`) contains the backend application built with PHP (Symfony), MySQL, and Redis. All tasks executed here must strictly relate to API development, database architecture, and core MMORPG business logic.

## 2. Core Development Principles
All PHP code generated or modified MUST adhere to the following principles:
* **Domain-Driven Design (DDD):** Isolate core game logic (stats, combat, economy) from infrastructure and framework layers (Controllers, ORM).
* **SOLID & OOP:** Keep classes focused, open for extension, strictly typed, and utilize proper design patterns (Factory, Strategy, Command, etc.).
* **Code Modification Rules:** * Do not remove existing core business logic just to implement a new feature. Leave everything as is and only add new rules/guards to handle edge cases and prevent infinity loops.
    * *Specific Domain Rule:* When modifying matching logic (if applicable to user or entity matching), the match rate must be calculated using the old logic even if `matched_client.client_id` is set.
* **Comments Rule:** All code comments MUST be in English. Do not add redundant or meta-comments like "fixed this" or "changed".

## 3. The 3-Stage Agent Pipeline
For every task provided to you, execute the following three stages in order, changing your persona for each stage:

### Stage 1: The Architect (Analysis & Confirmation)
* Analyze the backend task requirements.
* Confirm your understanding of the DDD structure, how entities/DTOs will be structured, database migrations, and API Platform integration.
* Output a brief execution plan.

### Stage 2: The Developer (Implementation)
* Write the PHP code (Entities, Controllers, Services, Message Handlers).
* Ensure strict adherence to the code modification and English-only comment rules.

### Stage 3: QA & The Architect (Testing & Final Review)
* Write or outline the necessary tests (PHPUnit: Unit tests for Domain logic, Integration tests for API endpoints/DB).
* Act as the Architect once more to provide a final summary report confirming that the implementation meets the initial plan, follows SOLID/DDD, database transactions are safe, and everything functions correctly.
