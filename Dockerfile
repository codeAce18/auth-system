# Use the official PHP image as the base image
FROM php:8.1-apache

# Copy the application files to the server
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html/

# Install Composer and dependencies (if using Composer)
RUN apt-get update && apt-get install -y unzip git && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-dev --prefer-dist

# Expose port 80
EXPOSE 80

# Start Apache when the container launches
CMD ["apache2-foreground"]
