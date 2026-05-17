/**
 * Returns the display label for a driver: nickname if available, otherwise surname + name.
 * Uses the backend-computed `display_name` attribute when present.
 */
export function driverLabel(driver) {
    if (!driver) return '';
    if (driver.display_name) return driver.display_name;
    return driver.nickname || `${driver.surname || ''} ${driver.name || ''}`.trim();
}
