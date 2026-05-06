import React from "react";
import "./project.css";

export const Projects = () => {
  return (
    <section className="projects" id="projects">
      <h1 className="projects-title">My Projects</h1>

      <div className="projects-container">
        {/* Project 1 */}
        <div className="project-card">
          <img src="src/assets/qms.png" alt="project" />
         <a href="https://qms.gpnainital.com/" target="_blank" rel="noopener noreferrer">
           <div className="project-content">
            <h3>Queue Management System</h3>
            <p>
              A Queue Management System efficiently organizes and controls
              customer flow to reduce waiting time and improve service quality.
            </p>
            <div className="tags">
              <span>React</span>
              <span>CSS</span>
            </div>
          </div>
         </a>
        </div>

        {/* Project 2 */}
      <a href="https://harshitbhatt07.github.io/WeatherApp/" target="_blank" rel="noopener noreferrer">
          <div className="project-card">
          <img src="src/assets/weater.png" alt="project" />
          <div className="project-content">
            <h3>Weather App</h3>
            <p>
              A Weather App using an API fetches real-time weather data from
              online servers to display current conditions and forecasts.
            </p>
            <div className="tags">
              <span>React</span>
              <span>CSS</span>
              <span>API</span>
            </div>
          </div>
        </div>
      </a>
        {/* Project 3 */}
        <div className="project-card">
          <img src="src/assets/hotel.png" alt="project" />
          <div className="project-content">
            <h3>Hotel Green Palace</h3>
            <p>
              I created Hotel Green Palace, a responsive hotel booking website
              using frontend technologies. It provides an easy and smooth
              experience for room booking..
            </p>
            <div className="tags">
              <span>HTML</span>
              <span>CSS</span>
              <span>JavaScript</span>
              <span>PHP</span>
              <span>MySQL</span>
            </div>
          </div>
        </div>
        <div className="project-card">
          <img src="src/assets/eco.png" alt="project" />
          <div className="project-content">
            <h3>E-Commerce</h3>
            <p>
              I developed a responsive e-commerce website using only frontend
              technologies. It provides a clean UI and smooth user experience
              for online shopping.
            </p>
            <div className="tags">
              <span>React</span>
              <span>css</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
