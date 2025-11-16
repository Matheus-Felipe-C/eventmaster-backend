FROM docker.io/eclipse-temurin:21-jdk

WORKDIR /app

COPY target/*.jar /app/eventmaster.jar

ENTRYPOINT ["java", "-jar", "eventmaster.jar"]
