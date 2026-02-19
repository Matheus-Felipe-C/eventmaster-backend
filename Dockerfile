# ===== Build stage =====
FROM maven:3.9.6-eclipse-temurin-21 AS build
WORKDIR /app

# Copy only pom first (better Docker caching)
COPY pom.xml .
RUN mvn -B dependency:go-offline

# Copy source
COPY src ./src

# Build
RUN mvn -B clean package -DskipTests

# ===== Runtime stage =====
FROM eclipse-temurin:21-jre
WORKDIR /app

# Copy jar
COPY --from=build /app/target/*.jar app.jar

# JVM optimizations for containers
ENV JAVA_OPTS="-XX:+UseContainerSupport -XX:MaxRAMPercentage=75"

EXPOSE 8080

ENTRYPOINT ["sh", "-c", "java $JAVA_OPTS -jar app.jar"]