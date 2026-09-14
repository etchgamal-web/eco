export type Permission = "products.view" | "products.manage" | "orders.view" | "orders.manage" | "customers.view" | "settings.manage";

export function can(permission: Permission, granted: Permission[] = []) {
  return granted.includes(permission);
}

export function canAny(permissions: Permission[], granted: Permission[] = []) {
  return permissions.some((permission) => can(permission, granted));
}
