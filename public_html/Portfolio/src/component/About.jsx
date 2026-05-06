import React from "react";
import "./About.css";
import profile from "../assets/mee4.jfif"; // apni image yaha daal

export const About = () => {
  return (
    <section className="about" id="aboutmee">
      <h1 className="about-title">
         <span>About Me</span>
      </h1>

      <div className="about-container">
        {/* Left Image */}
        <div className="about-image">
          <img src={profile} alt="profile" />
        </div>

        {/* Right Content */}
        <div className="about-content">
          <p className="intro">
            I am a Diploma student and an aspiring Web Developer passionate
            about creating interactive and user-friendly websites. I enjoy
            writing clean code and continuously learning new technologies to
            improve my skills. My goal is to become a Software Engineer and
            build modern, responsive web applications that deliver a great user
            experience.
          </p>

          <div className="info-box">
            <span>🎓</span>
            <p>
              <b>Education:</b> Diploma in Information Technology 
            </p>
          </div>

          <div className="info-box">
            <span>🧠</span>
            <p>
              <b>Strengths:</b> Problem Solving, Quick Learner, Teamwork
            </p>
          </div>

          <div className="info-box">
            <span>🛠️</span>
            <p>
              <b>Tools & Technologies:</b> Git, GitHub, VS Code, APIs
            </p>
          </div>

          <p className="goal">
            My goal is to become a full-stack developer and build innovative,
            scalable web applications. I am continuously working on enhancing my
            skills and expanding my knowledge to stay updated with modern
            technologies and industry trends.
          </p>

          <button className="btn">Read More</button>
        </div>
      </div>
    </section>
  );
};