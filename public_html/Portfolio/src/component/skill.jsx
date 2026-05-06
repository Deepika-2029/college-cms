import React from "react";
import "./skill.css";

export const Skills = () => {
  return (
    <section className="skills" id="skills">
      <h1 className="skills-title">
        <span>My Skills</span>
      </h1>

      <div className="skills-container">

        <div className="skill-card">
          <img src="src/assets/c.png" alt="HTML" className="skill-img" />
          <h3>C</h3>
          <div className="progress">
            <div className="progress-bar html"></div>
          </div>
          <span>80%</span>
        </div>
        <div className="skill-card">
          <img src="src/assets/c++.png" alt="HTML"  className="skill-img" />
          <h3>C++</h3>
          <div className="progress">
            <div className="progress-bar html"></div>
          </div>
          <span>50%</span>
        </div>
        <div className="skill-card">
          <img src="src/assets/html.png" alt="HTML"  className="skill-img" />
          <h3>HTML</h3>
          <div className="progress">
            <div className="progress-bar html"></div>
          </div>
          <span>90%</span>
        </div>

        <div className="skill-card">
          <img src="src/assets/css.jfif" alt="CSS" className="skill-img"  />
          <h3>CSS</h3>
          <div className="progress">
            <div className="progress-bar css"></div>
          </div>
          <span>85%</span>
        </div>

        <div className="skill-card">
          <img src="src/assets/javascript.png" alt="JS"  className="skill-img" />
          <h3>JavaScript</h3>
          <div className="progress">
            <div className="progress-bar js"></div>
          </div>
          <span>80%</span>
        </div>

        <div className="skill-card">
          <img src="src/assets/react.png" alt="React"  className="skill-img" />
          <h3>React</h3>
          <div className="progress">
            <div className="progress-bar react"></div>
          </div>
          <span>60%</span>
        </div>

        <div className="skill-card">
          <img src="src/assets/railwind.png" alt="Tailwind"   className="skill-img"/>
          <h3>Tailwind CSS</h3>
          <div className="progress">
            <div className="progress-bar tailwind"></div>
          </div>
          <span>80%</span>
        </div>
        <div className="skill-card">
          <img src="src/assets/bootstaaap.png" alt="Tailwind"  className="skill-img" />
          <h3>Bootstrap </h3>
          <div className="progress">
            <div className="progress-bar tailwind"></div>
          </div>
          <span>80%</span>
        </div>

      </div>
    </section>
  );
};
