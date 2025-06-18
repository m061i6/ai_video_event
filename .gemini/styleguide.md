# ai_video_event 專案程式碼風格指南 (Style Guide)

本文件定義了專案的程式碼撰寫標準、命名慣例和最佳實踐。所有程式碼審查都應以此文件為主要參考依據。

## 1. 通用原則 (General Principles)

- **語言**: 所有註解、Git Commit Message、文件都應使用**繁體中文 (Traditional Chinese)**。
- **清晰度**: 程式碼應易於理解。優先選擇清晰的寫法，而不是取巧的短碼。

## 2. 命名慣例 (Naming Conventions)

| 類別 | 格式 | 範例 |
| --- | --- | --- |
| PHP 變數 | `camelCase` | `$orderDetail` |
| PHP 函式/方法 | `camelCase` | `calculateTotalAmount()` |
| PHP Class | `PascalCase` | `ProductService`, `OrderController` |
| PHP Class 常數 | `UPPER_CASE_SNAKE` | `const DEFAULT_STATUS = 'pending';` |
| 資料庫表單 | `snake_case` (複數) | `order_details`, `products` |
| 資料庫欄位 | `snake_case` | `user_id`, `created_at` |
| JavaScript 變數/函式 | `camelCase` | `const userProfile`, `function fetchItems()` |
| Vue 元件檔名 | `PascalCase.vue` | `UserProfileCard.vue` |
| Vue Props (JS) | `camelCase` | `userId` |
| Vue Props (template) | `kebab-case` | `user-id` |
| CSS Class (自訂) | `kebab-case` | `.custom-card-header` |

---

## 3. PHP & Laravel

- **遵循 PSR-12**: 程式碼應遵循 PSR-12 擴展程式碼風格指南。
- **型別提示 (Type Hinting)**: 函式/方法的參數及回傳值應盡可能加上型別提示。
- **嚴格模式 (Strict Types)**: 建議在新 PHP 檔案開頭使用 `declare(strict_types=1);`。
  ```php
  <?php declare(strict_types=1);

  namespace App\Services;
  // ...
  ```

### Controller (控制器)
- **保持精簡 (Thin Controllers)**：Controller 只負責接收請求、驗證輸入、呼叫 Service 處理商業邏輯，並回傳響應。
- **✓ Do:**
  ```php
  public function store(StoreOrderRequest $request)
  {
      $order = $this->orderService->create($request->validated());
      return new OrderResource($order);
  }
  ```

### Blade & TailwindCSS
- Blade 只負責顯示，資料查詢與處理請於 Controller 完成。
- Blade 變數請用 `{{ $variable }}` 輸出，避免 `{!! $variable !!}` 除非信任內容。
- 多語系請用 Laravel 的語系檔，不要寫死在 blade。
- 前端樣式統一使用 TailwindCSS，勿混用 inline style。
- 自訂 CSS 請集中於 `resources/css`，命名採用 kebab-case。
- JS 事件與資料綁定請用 unobtrusive 方式，勿直接寫在 HTML 屬性。

---