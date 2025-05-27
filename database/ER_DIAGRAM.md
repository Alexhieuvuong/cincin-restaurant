# Entity Relationship Diagram

## Tables and Relationships

```
users
  id (PK)
  name
  email
  password
  address
  phone
  is_admin
  created_at
  updated_at
  |
  |-- 1:N --> orders
  |-- 1:N --> cart

categories
  id (PK)
  name
  description
  image
  created_at
  updated_at
  |
  |-- 1:N --> products

products
  id (PK)
  category_id (FK)
  name
  description
  price
  image
  is_available
  created_at
  updated_at
  |
  |-- N:1 --> categories
  |-- 1:N --> order_items
  |-- 1:N --> cart

orders
  id (PK)
  user_id (FK)
  total_amount
  status
  payment_status
  address
  phone
  created_at
  updated_at
  |
  |-- N:1 --> users
  |-- 1:N --> order_items
  |-- 1:1 --> payments

order_items
  id (PK)
  order_id (FK)
  product_id (FK)
  quantity
  price
  created_at
  |
  |-- N:1 --> orders
  |-- N:1 --> products

payments
  id (PK)
  order_id (FK)
  amount
  payment_method
  transaction_id
  status
  created_at
  updated_at
  |
  |-- 1:1 --> orders

cart
  id (PK)
  user_id (FK)
  product_id (FK)
  quantity
  created_at
  updated_at
  |
  |-- N:1 --> users
  |-- N:1 --> products
```

## Relationship Descriptions

1. A **user** can place multiple **orders**, but each order belongs to only one user.
2. A **user** can have multiple items in their **cart**.
3. A **category** can have multiple **products**, but each product belongs to only one category.
4. An **order** can contain multiple **order_items**, and each order item belongs to one order.
5. A **product** can be in multiple **order_items**, but each order item references one product.
6. An **order** has one **payment**, and each payment belongs to one order.
7. A **product** can be in multiple users' **cart**. 